<?php

namespace App\Http\Controllers;

use App\Models\DispatchLog;
use App\Models\Order;
use App\Models\ProductionLog;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Worker;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistoryPdfController extends Controller
{
    private function authUser(): array
    {
        return session('auth_user');
    }

    public function download(Request $request, string $panel)
    {
        $panel = strtoupper($panel);
        abort_unless(in_array($panel, ['RAW', 'SEMI', 'FINISHED', 'SALES', 'DISPATCH', 'CASHIER', 'ATTENDANCE'], true), 404);
        $user = $this->authUser();
        abort_unless(($user['role'] ?? null) === 'ADMIN' || ($user['role'] ?? null) === $panel, 403);

        if ($panel === 'DISPATCH') {
            $data = $this->buildDispatchReportData($request);
            $pdf = Pdf::loadView('pdf.dispatch-history-report', $data)->setPaper('A4', 'portrait');
        } else {
            $data = $this->buildReportData($request, $panel);
            $pdf = Pdf::loadView('pdf.history-report', $data)->setPaper('A4', 'portrait');
        }

        return $pdf->download('PentaPure_' . ucfirst(strtolower($panel)) . '_History_' . now()->format('Ymd_His') . '.pdf')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function buildReportData(Request $request, string $panel): array
    {
        $user = $this->authUser();
        [$from, $to] = $this->dateRange($request);
        $rows = match ($panel) {
            'RAW' => $this->rawRows($from, $to),
            'SEMI', 'FINISHED' => $this->productionRows($panel, $from, $to),
            'SALES' => $this->salesRows($from, $to),
            'DISPATCH' => $this->dispatchRows($from, $to),
            'ATTENDANCE' => $this->attendanceRows($request),
            default => $this->cashierRows($user, $from, $to),
        };

        $purchaseOrders = [];
        if (in_array($panel, ['RAW', 'SEMI', 'FINISHED'], true)) {
            $purchaseOrders = \App\Models\PurchaseOrder::with('product')
                ->where('user_id', $user['id'])
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->get()
                ->map(fn($po) => [
                    'id' => 'PO-' . str_pad($po->id, 4, '0', STR_PAD_LEFT),
                    'date' => $po->created_at->format('d M Y'),
                    'material' => $po->product?->name ?? '-',
                    'quantity' => $po->quantity,
                    'status' => $po->status === 'DONE' ? 'READ BY ADMIN' : $po->status,
                ])->toArray();
        }

        $amountTotal = collect($rows)->sum(fn ($row) => (float) ($row['amount'] ?? 0));
        $completed = collect($rows)->whereIn('status', ['DONE', 'CLOSED', 'COMPLETED', 'PRESENT'])->count();
        $pending = collect($rows)->whereIn('status', ['PENDING', 'OPEN', 'ABSENT'])->count();

        return [
            'isAttendance' => $panel === 'ATTENDANCE',
            'panel' => ucfirst(strtolower($panel)),
            'reportId' => 'HIS-' . now()->format('His'),
            'generatedOn' => now()->format('d M Y'),
            'fromDate' => $panel === 'ATTENDANCE' ? Carbon::parse($request->month ?? now())->startOfMonth()->format('d M Y') : $from->format('d M Y'),
            'toDate' => $panel === 'ATTENDANCE' ? Carbon::parse($request->month ?? now())->endOfMonth()->format('d M Y') : $to->format('d M Y'),
            'userName' => $user['name'] ?? 'User',
            'userRole' => $user['role'] ?? $panel,
            'rows' => $rows,
            'purchaseOrders' => $purchaseOrders,
            'totalRecords' => count($rows),
            'completed' => $completed,
            'pending' => $pending,
            'approved' => collect($rows)->whereIn('status', ['APPROVED', 'DONE', 'CLOSED'])->count(),
            'amountTotal' => $amountTotal,
        ];
    }

    private function dateRange(Request $request): array
    {
        $fromInput = $request->from ?: $request->start;
        $toInput = $request->to ?: $request->end;

        if ($request->range && $request->range !== 'all' && !$fromInput && !$toInput) {
            $range = $request->range;
            if ($range === 'today') {
                $from = now()->startOfDay();
                $to = now()->endOfDay();
            } elseif ($range === 'this_week') {
                $from = now()->startOfWeek();
                $to = now()->endOfDay();
            } elseif ($range === 'last_week') {
                $from = now()->subWeek()->startOfWeek();
                $to = now()->subWeek()->endOfWeek();
            } elseif ($range === 'this_month') {
                $from = now()->startOfMonth();
                $to = now()->endOfDay();
            } elseif ($range === 'last_month') {
                $from = now()->subMonth()->startOfMonth();
                $to = now()->subMonth()->endOfWeek();
            }
        }

        $from = isset($from) ? $from : ($fromInput ? Carbon::parse($fromInput)->startOfDay() : now()->subDays(30)->startOfDay());
        $to = isset($to) ? $to : ($toInput ? Carbon::parse($toInput)->endOfDay() : now()->endOfDay());
        return [$from, $to];
    }

    private function rawRows(Carbon $from, Carbon $to): array
    {
        $q = request('q');
        $query = Stock::with(['product', 'location'])->where('stage', 'RAW')->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('product', function($qp) use ($q) {
                    $qp->where('name', 'like', "%{$q}%");
                })->orWhere('grade', 'like', "%{$q}%")
                  ->orWhereHas('location', function($ql) use ($q) {
                      $ql->where('name', 'like', "%{$q}%");
                  });
            });
        }
        return $query->latest()->get()
            ->map(fn ($s) => [
                'id' => 'RAW-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                'type' => $s->transaction_type === 'IN' ? 'IN' : 'OUT',
                'date' => $s->created_at->format('d M Y, h:i A'),
                'status' => 'COMPLETED',
                'amount' => 0,
                'product_name' => $s->product ? $s->product->formatName($s->grade) : '-',
                'grade' => $s->grade ?? '-',
                'location' => $s->location?->name ?? 'Unassigned',
                'quantity' => $s->quantity ?? 0,
                'unit' => $s->product?->unit ?? 'kg',
                'notes' => $s->notes ?? '—',
                'transaction_type' => $s->transaction_type
            ])->toArray();
    }

    private function productionRows(string $type, Carbon $from, Carbon $to): array
    {
        $q = request('q');
        $query = ProductionLog::with(['outputProduct', 'inputs.inputProduct'])->where('type', $type)->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('outputProduct', function($qp) use ($q) {
                    $qp->where('name', 'like', "%{$q}%");
                })->orWhere('output_grade', 'like', "%{$q}%");
            });
        }
        return $query->latest()->get()
            ->map(fn ($l) => [
                'id' => $type . '-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                'type' => ucfirst(strtolower($type)) . ' Production',
                'date' => $l->created_at->format('d M Y'),
                'status' => 'COMPLETED',
                'amount' => 0,
                'output_product' => $l->outputProduct ? $l->outputProduct->formatName($l->output_grade) : '-',
                'output_grade' => $l->output_grade ?? '-',
                'output_qty' => $l->output_qty ?? 0,
                'unit' => $l->outputProduct?->unit ?? 'kg',
                'inputs' => collect($l->inputs)->map(fn($input) => [
                    'name' => $input->inputProduct ? $input->inputProduct->formatName($input->input_grade) : '-',
                    'grade' => $input->input_grade ?? '-',
                    'quantity' => $input->quantity ?? 0,
                ])->toArray(),
            ])->toArray();
    }

    private function salesRows(Carbon $from, Carbon $to): array
    {
        $q = request('q');
        $query = Order::with(['company', 'items'])->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('notes', 'like', "%{$q}%")
                  ->orWhere('id', 'like', "%{$q}%");
            });
        }
        return $query->latest()->get()
            ->map(fn ($o) => [
                'id' => 'ORD-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'type' => 'Order',
                'date' => $o->created_at->format('d M Y'),
                'status' => $o->status,
                'dispatch_status' => $o->dispatch_status,
                'amount' => (float) $o->total,
                'company_name' => $o->company?->name ?? '-',
                'total_items' => count($o->items),
                'total_qty' => collect($o->items)->sum('quantity'),
            ])->toArray();
    }

    private function dispatchRows(Carbon $from, Carbon $to): array
    {
        $q = request('q');
        $query = DispatchLog::with(['order.company', 'dispatchItems'])->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('order.company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('order_id', 'like', "%{$q}%");
            });
        }
        return $query->latest()->get()
            ->map(fn ($d) => [
                'id' => 'DSP-' . str_pad($d->id, 4, '0', STR_PAD_LEFT),
                'type' => 'Dispatch',
                'date' => $d->created_at->format('d M Y'),
                'status' => $d->lr_image_path ? 'DONE' : 'PENDING',
                'amount' => (float) ($d->order?->total ?? 0),
                'description' => 'Order #' . $d->order_id . ' - ' . ($d->order?->company?->name ?? 'Company') . ' - ' . $d->dispatchItems->sum('quantity') . ' kg',
                'lr_copy' => $d->lr_image_path,
            ])->toArray();
    }

    private function cashierRows(array $user, Carbon $from, Carbon $to): array
    {
        return Transaction::where('user_id', $user['id'])->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn ($t) => [
                'id' => 'TXN-' . str_pad($t->id, 4, '0', STR_PAD_LEFT),
                'type' => $t->type === 'IN' ? 'Income' : 'Expense',
                'date' => $t->created_at->format('d M Y'),
                'status' => 'COMPLETED',
                'amount' => $t->type === 'OUT' ? -1 * (float) $t->amount : (float) $t->amount,
                'category' => $t->category ?? 'General',
                'note' => $t->note ?? '',
                'reference' => $t->reference ?? '',
            ])->toArray();
    }

    private function attendanceRows(Request $request): array
    {
        $month = $request->month ?? now()->format('Y-m');
        $start = Carbon::parse($month)->startOfMonth()->toDateString();
        $end = Carbon::parse($month)->endOfMonth()->toDateString();

        return Worker::with(['department', 'attendances' => fn ($q) => $q->whereBetween('date', [$start, $end])])->where('status', 'ACTIVE')->orderBy('name')->get()
            ->map(function ($w) {
                $present = $w->attendances->where('status', 'PRESENT')->count();
                $absent = $w->attendances->where('status', 'ABSENT')->count();
                $half = $w->attendances->where('status', 'HALF_DAY')->count();
                return [
                    'id' => 'WRK-' . str_pad($w->id, 4, '0', STR_PAD_LEFT),
                    'type' => $w->department?->name ?? 'Attendance',
                    'date' => now()->format('d M Y'),
                    'status' => $absent > 0 ? 'PENDING' : 'COMPLETED',
                    'amount' => (float) $w->attendances->sum('calculated_wage'),
                    'employee' => $w->name,
                    'department' => $w->department?->name ?? '-',
                    'salary' => (float) $w->salary_amount,
                    'salaryType' => $w->salary_type,
                    'present' => $present,
                    'half' => $half,
                    'absent' => $absent,
                    'ot' => (float) $w->attendances->sum('overtime_hours'),
                    'payable' => (float) $w->attendances->sum('calculated_wage'),
                    'description' => "{$w->name}: {$present} present, {$half} half day, {$absent} absent",
                ];
            })->toArray();
    }

    public function salesOrderPdf(Request $request, $id)
    {
        $order = Order::with([
            'company',
            'transporter',
            'creator',
            'items.product'
        ])->findOrFail($id);

        $totalOrderedQty = 0;
        $totalAmount = 0;

        foreach ($order->items as $item) {
            $totalOrderedQty += (float) $item->quantity;
            $totalAmount += (float) ($item->price * $item->quantity);
        }

        $data = [
            'order' => $order,
            'company' => $order->company,
            'transporter' => $order->transporter ?? (object)[],
            'items' => $order->items,
            'orderNo' => 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            'orderDate' => $order->created_at->format('d-M-Y'),
            'generatedOn' => now()->format('d-M-Y h:i A'),
            'generatedBy' => $this->authUser()['name'] ?? 'System',
            'status' => $order->status,
            'remarks' => $order->notes ?? '',
            'totalOrderedQty' => $totalOrderedQty,
            'totalAmount' => $totalAmount,
            'totalItems' => count($order->items),
        ];

        $pdf = Pdf::loadView('pdf.sales-order', $data)->setPaper('A4', 'portrait');
        return $pdf->download($data['orderNo'] . '_Sales_Order_' . now()->format('Ymd_His') . '.pdf');
    }

    public function dispatchNotePdf(Request $request, $id)
    {
        $log = DispatchLog::with([
            'order.company',
            'order.transporter',
            'order.creator',
            'user',
            'transporter',
            'dispatchItems.orderItem.product'
        ])->findOrFail($id);

        $order = $log->order;
        
        $totalOrderedQty = 0;
        $totalPrevDispatchedQty = 0;
        $totalDispatchedQty = 0;
        $totalPendingQty = 0;
        $totalAmount = 0;

        foreach ($log->dispatchItems as $di) {
            $orderItem = $di->orderItem;
            if ($orderItem) {
                $totalOrderedQty += (float) $orderItem->quantity;
                $totalDispatchedQty += (float) $di->quantity;
                $prevDispatched = (float) $orderItem->dispatched_qty - (float) $di->quantity;
                $totalPrevDispatchedQty += $prevDispatched;
                // Pending quantity after this dispatch log round
                $totalPendingQty += (float) max(0, $orderItem->quantity - $orderItem->dispatched_qty);
                $totalAmount += (float) ($orderItem->price * $di->quantity);
            }
        }

        $dispatchHistory = \App\Models\DispatchLog::with(['dispatchItems.orderItem.product', 'user'])
            ->where('order_id', $order->id)
            ->where('id', '<=', $log->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $data = [
            'log' => $log,
            'order' => $order,
            'company' => $order->company,
            'transporter' => $log->transporter ?? $order->transporter,
            'items' => $log->dispatchItems,
            'dispatchHistory' => $dispatchHistory,
            'dispatchNo' => 'DSP-' . str_pad($log->id, 4, '0', STR_PAD_LEFT),
            'orderNo' => 'ORD-' . str_pad($log->order_id, 4, '0', STR_PAD_LEFT),
            'dispatchDate' => $log->created_at->format('d-M-Y'),
            'generatedOn' => $log->created_at->format('d-M-Y h:i A'),
            'generatedBy' => $log->user?->name ?? 'System',
            'orderGeneratedBy' => $order->creator?->name ?? 'N/A',
            'status' => 'DISPATCHED',
            'remarks' => $order->notes ?? "Material dispatched in good condition.\nAll items checked and verified before dispatch.",
            'totalOrderedQty' => $totalOrderedQty,
            'totalPrevDispatchedQty' => $totalPrevDispatchedQty,
            'totalDispatchedQty' => $totalDispatchedQty,
            'totalPendingQty' => $totalPendingQty,
            'totalAmount' => $totalAmount,
            'totalItems' => count($log->dispatchItems),
            'dispatchType' => ($totalPendingQty <= 0) ? 'Full Dispatch' : 'Partial Dispatch',
        ];

        $pdf = Pdf::loadView('pdf.dispatch-note', $data)->setPaper('A4', 'portrait');

        return $pdf->download($data['dispatchNo'] . '_Dispatch_Note_' . now()->format('Ymd_His') . '.pdf');
    }

    private function buildDispatchReportData(Request $request): array
    {
        $user = $this->authUser();
        [$from, $to] = $this->dateRange($request);
        
        $query = DispatchLog::with([
            'order.company',
            'order.transporter',
            'order.creator',
            'user',
            'dispatchItems.orderItem.product',
            'dispatchItems.locationAllocations.location'
        ])->whereBetween('created_at', [$from, $to]);

        $q = $request->q;
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('order.company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('order_id', 'like', "%{$q}%")
                  ->orWhereHas('dispatchItems.orderItem.product', function($qp) use ($q) {
                      $qp->where('name', 'like', "%{$q}%");
                  });
            });
        }
        $logs = $query->latest()->get();
        
        $rows = [];
        $totalQty = 0;
        $totalOrderedQty = 0;
        $totalPendingQty = 0;
        $totalAmount = 0;
        $completedCount = 0;
        $pendingCount = 0;
        
        $uniqueOrders = [];

        foreach ($logs as $log) {
            $order = $log->order;
            if ($order) {
                $uniqueOrders[$order->id] = $order;
            }
            
            $status = ($order && $order->dispatch_status === 'DONE') ? 'COMPLETED' : 'PENDING';
            
            $formatQty = fn($q, $u) => number_format($q, floor($q) == $q ? 0 : 2) . ' ' . $u;

            foreach ($log->dispatchItems as $di) {
                $orderItem = $di->orderItem;
                if ($orderItem) {
                    $qty = (float) $di->quantity;
                    $orderedQty = (float) $orderItem->quantity;
                    $pendingQty = max(0, $orderedQty - (float) $orderItem->dispatched_qty);
                    $rate = (float) $orderItem->price;
                    $amount = $qty * $rate;
                    $unit = strtoupper($orderItem->product?->unit ?? 'KG');

                    $totalQty += $qty;
                    $totalOrderedQty += $orderedQty;
                    $totalPendingQty += $pendingQty;
                    $totalAmount += $amount;

                    $locations = $di->locationAllocations
                        ->map(fn($loc) => $loc->location->name ?? 'Unknown')
                        ->unique()
                        ->implode(', ');
                    
                    if (empty($locations)) {
                        $locations = 'N/A';
                    }
                    
                    $rows[] = [
                        'dispatch_id' => 'DSP-' . str_pad($log->id, 4, '0', STR_PAD_LEFT),
                        'order_id' => 'ORD-' . str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT),
                        'date' => $log->created_at->format('d M Y'),
                        'customer' => strtoupper($order?->company?->name ?? 'N/A'),
                        'product' => strtoupper($orderItem->product ? $orderItem->product->formatName($orderItem->grade) : 'Unknown'),
                        'grade' => strtoupper($orderItem->grade ?? 'NONE'),
                        'locations' => $locations,
                        'ordered_qty' => $orderedQty,
                        'ordered_qty_formatted' => $formatQty($orderedQty, $unit),
                        'qty' => $qty,
                        'dispatch_qty_formatted' => $formatQty($qty, $unit),
                        'pending_qty' => $pendingQty,
                        'pending_qty_formatted' => $formatQty($pendingQty, $unit),
                        'amount' => $amount,
                        'rate' => $rate,
                        'unit' => $unit,
                        'status' => $status,
                        'lr_copy' => $log->lr_image_path,
                    ];
                }
            }
        }

        // Count statuses based on order state
        foreach ($uniqueOrders as $order) {
            if ($order->dispatch_status === 'DONE') {
                $completedCount++;
            } else {
                $pendingCount++;
            }
        }

        $customerSummary = [];
        $productSummary = [];

        foreach (collect($rows)->groupBy('customer') as $custName => $custRows) {
            $customerSummary[] = [
                'customer' => $custName,
                'count' => $custRows->unique('dispatch_id')->count(),
                'qty' => $custRows->sum('qty'),
            ];
        }

        foreach (collect($rows)->groupBy('product') as $prodName => $prodRows) {
            $productSummary[] = [
                'product' => $prodName,
                'count' => $prodRows->unique('dispatch_id')->count(),
                'qty' => $prodRows->sum('qty'),
            ];
        }

        // Fetch LR copies to show at the bottom
        $lrCopies = $logs->filter(fn($l) => !empty($l->lr_image_path))
            ->map(fn($l) => [
                'dispatch_id' => 'DSP-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                'order_id' => 'ORD-' . str_pad($l->order_id, 4, '0', STR_PAD_LEFT),
                'customer' => $l->order?->company?->name ?? 'N/A',
                'path' => $l->lr_image_path
            ])->toArray();

        return [
            'reportId' => 'HIS-' . now()->format('His'),
            'generatedOn' => now()->format('d M Y h:i A'),
            'fromDate' => $from->format('d M Y'),
            'toDate' => $to->format('d M Y'),
            'userName' => $user['name'] ?? 'User',
            'userRole' => $user['role'] ?? 'DISPATCH',
            'rows' => $rows,
            'totalRecords' => $logs->count(),
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'cancelledCount' => 0, // No cancelled status in DB schema currently
            'totalValue' => $totalAmount,
            'totalQuantity' => $totalQty,
            'totalOrderedQty' => $totalOrderedQty,
            'totalPendingQty' => $totalPendingQty,
            'customerSummary' => $customerSummary,
            'productSummary' => $productSummary,
            'lrCopies' => $lrCopies,
        ];
    }
}
