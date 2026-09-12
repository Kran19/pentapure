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
        abort_unless(($user['role'] ?? null) === 'ADMIN' || in_array($panel, ['RAW', 'SEMI', 'FINISHED', 'SALES', 'DISPATCH', 'CASHIER', 'ATTENDANCE'], true), 403);

        if ($panel === 'DISPATCH') {
            $data = $this->buildDispatchReportData($request);
            $pdf = Pdf::loadView('pdf.dispatch-history-report', $data)->setPaper('A4', 'portrait');
        } else {
            $data = $this->buildReportData($request, $panel);
            $pdf = Pdf::loadView('pdf.history-report', $data)->setPaper('A4', 'portrait');
        }

        [$from, $to] = $this->dateRange($request);
        $formattedFromDate = $from ? $from->format('d-m-Y') : now()->format('d-m-Y');
        $formattedToDate   = $to   ? $to->format('d-m-Y')   : now()->format('d-m-Y');
        
        if ($panel === 'SALES') {
            if ($from && $to && $from->format('Y-m-d') !== $to->format('Y-m-d')) {
                $filename = 'PENTAPURE_SALES_HISTORY_' . $formattedFromDate . 'TO' . $formattedToDate . '.pdf';
            } else {
                $filename = 'PENTAPURE_SALES_HISTORY_' . $formattedFromDate . '.pdf';
            }
        } elseif ($panel === 'DISPATCH') {
            $companyName = 'ALL-CUSTOMERS';
            if ($request->company_id) {
                $comp = \App\Models\Company::find($request->company_id);
                if ($comp) {
                    $companyName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $comp->name));
                }
            }

            $statusStr = 'ALL-STATUS';
            if ($request->status) {
                $statusStr = strtoupper(trim(str_replace('_', '-', $request->status)));
            }

            $dateStr = ($from && $to && $from->format('Y-m-d') !== $to->format('Y-m-d')) 
                ? ($formattedFromDate . 'TO' . $formattedToDate) 
                : $formattedFromDate;

            $filename = 'DISPATCH_' . $companyName . '_' . $statusStr . '_' . $dateStr . '.pdf';
        } else {
            $randomSerial = rand(1000, 9999);
            if ($from && $to && $from->format('Y-m-d') !== $to->format('Y-m-d')) {
                $filename = 'PENTAPURE_' . strtoupper($panel) . '_' . $formattedFromDate . 'TO' . $formattedToDate . '_' . $randomSerial . '.pdf';
            } else {
                $filename = 'PENTAPURE_' . strtoupper($panel) . '_' . $formattedFromDate . '_' . $randomSerial . '.pdf';
            }
        }

        return $pdf->download($filename)
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

        if (($request->range === 'all' || !$request->range) && !$fromInput && !$toInput) {
            $earliestOrderDate = Order::min('created_at');
            $from = $earliestOrderDate ? Carbon::parse($earliestOrderDate)->startOfDay() : Carbon::parse('2020-01-01')->startOfDay();
            $to = now()->endOfDay();
        } else {
            $from = isset($from) ? $from : ($fromInput ? Carbon::parse($fromInput)->startOfDay() : Carbon::parse('2020-01-01')->startOfDay());
            $to = isset($to) ? $to : ($toInput ? Carbon::parse($toInput)->endOfDay() : now()->endOfDay());
        }

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
        $companyId = request('company_id');
        $statusFilter = request('status');

        $query = Order::with(['company', 'items'])->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('notes', 'like', "%{$q}%")
                  ->orWhere('id', 'like', "%{$q}%");
            });
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($statusFilter) {
            $query->where(function($sub) use ($statusFilter) {
                if ($statusFilter === 'PENDING') {
                    $sub->where('status', 'CANCELLED')
                        ->orWhereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED'])
                        ->orWhereNull('dispatch_status');
                } elseif ($statusFilter === 'PARTIAL_PENDING') {
                    $sub->where('status', '!=', 'CANCELLED')
                        ->whereIn('dispatch_status', ['PARTIAL_PENDING', 'PARTIAL PENDING']);
                } elseif ($statusFilter === 'PARTIAL') {
                    $sub->where('status', '!=', 'CANCELLED')
                        ->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED']);
                } elseif ($statusFilter === 'DONE') {
                    $sub->where('status', '!=', 'CANCELLED')
                        ->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'DISPATCHED']);
                }
            });
        }

        return $query->latest()->get()
            ->map(function ($o) {
                $oStatus = strtoupper($o->status ?? '');
                $dStatus = strtoupper(str_replace('_', ' ', $o->dispatch_status ?? 'PENDING'));

                if ($oStatus === 'CANCELLED' || $dStatus === 'PENDING' || $dStatus === 'UNASSIGNED' || empty($dStatus)) {
                    $dispStatusFormatted = 'PENDING';
                } elseif ($dStatus === 'PARTIAL PENDING') {
                    $dispStatusFormatted = 'PARTIAL PENDING';
                } elseif ($dStatus === 'PARTIAL' || $dStatus === 'PARTIAL DISPATCH' || $dStatus === 'PARTIALLY DISPATCHED') {
                    $dispStatusFormatted = 'PARTIAL DISPATCH';
                } else {
                    $dispStatusFormatted = 'FULLY DISPATCHED';
                }

                return [
                    'id' => 'ORD-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'Order',
                    'date' => $o->created_at->format('d M Y'),
                    'status' => $o->status,
                    'dispatch_status' => $dispStatusFormatted,
                    'amount' => (float) $o->total,
                    'company_name' => $o->company?->name ?? '-',
                    'total_items' => count($o->items),
                    'total_qty' => collect($o->items)->sum('quantity'),
                ];
            })->toArray();
    }

    private function dispatchRows(Carbon $from, Carbon $to): array
    {
        $q = request('q');
        $companyId = request('company_id');
        $status = request('status');

        $query = DispatchLog::with(['order.company', 'dispatchItems'])->whereBetween('created_at', [$from, $to]);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('order.company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('order_id', 'like', "%{$q}%");
            });
        }
        if ($companyId) {
            $query->whereHas('order', function($qo) use ($companyId) {
                $qo->where('company_id', $companyId);
            });
        }
        if ($status) {
            $target = strtoupper(trim(str_replace('_', ' ', $status)));
            $query->whereHas('order', function($qo) use ($target) {
                if ($target === 'FULLY DISPATCHED' || $target === 'DONE') {
                    $qo->whereIn('dispatch_status', ['DONE', 'FULLY_DISPATCHED']);
                } elseif ($target === 'PARTIAL DISPATCH' || $target === 'PARTIAL') {
                    $qo->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING']);
                } elseif ($target === 'PENDING') {
                    $qo->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED']);
                } else {
                    $qo->where('dispatch_status', 'like', "%{$target}%");
                }
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

        $rawSt = strtoupper(trim(str_replace('_', '-', (string)($order->dispatch_status ?? $order->status ?? 'PENDING'))));
        if ($rawSt === 'DONE' || $rawSt === 'FULLY-DISPATCHED' || $rawSt === 'FULLY DISPATCHED') {
            $statusTitle = 'FULLY DISPATCH ORDER';
            $statusSlug = 'FULLY-DISPATCH';
        } elseif ($rawSt === 'PARTIAL-PENDING' || $rawSt === 'PARTIAL PENDING') {
            $statusTitle = 'PARTIAL PENDING ORDER';
            $statusSlug = 'PARTIAL-PENDING';
        } elseif ($rawSt === 'PARTIAL' || $rawSt === 'PARTIAL-DISPATCH' || $rawSt === 'PARTIAL DISPATCH') {
            $statusTitle = 'PARTIAL DISPATCH ORDER';
            $statusSlug = 'PARTIAL-DISPATCH';
        } else {
            $statusTitle = 'PENDING ORDER';
            $statusSlug = 'PENDING';
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
            'orderBy' => $order->creator?->name ?? 'N/A',
            'status' => $order->status,
            'statusTitle' => $statusTitle,
            'remarks' => $order->notes ?? '',
            'totalOrderedQty' => $totalOrderedQty,
            'totalAmount' => $totalAmount,
            'totalItems' => count($order->items),
        ];

        $companyNameClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order->company?->name ?? 'COMPANY'));
        $createdDateStr = $order->created_at ? $order->created_at->format('d-m-Y') : now()->format('d-m-Y');
        $pdfFilename = 'DISPATCH_' . strtoupper($data['orderNo']) . '_' . $companyNameClean . '_' . $statusSlug . '_' . $createdDateStr . '.pdf';

        $pdf = Pdf::loadView('pdf.sales-order', $data)->setPaper('A4', 'portrait');
        return $pdf->download($pdfFilename);
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
            if ($orderItem && $orderItem->order_id == $log->order_id) {
                $totalOrderedQty += (float) $orderItem->quantity;
                $totalDispatchedQty += (float) $di->quantity;
                $prevDispatched = (float) $orderItem->dispatched_qty - (float) $di->quantity;
                $totalPrevDispatchedQty += $prevDispatched;
                // Pending quantity after this dispatch log round
                $totalPendingQty += (float) max(0, $orderItem->quantity - $orderItem->dispatched_qty);
                $totalAmount += (float) ($orderItem->price * $di->quantity);
            }
        }

        $dispatchHistory = DispatchLog::with(['dispatchItems.orderItem.product', 'user'])
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
            'orderDate' => $order->created_at->format('d-M-Y'),
            'dispatchDate' => $log->created_at->format('d-M-Y'),
            'generatedOn' => $log->created_at->format('d-M-Y h:i A'),
            'generatedBy' => $log->user?->name ?? 'System',
            'orderGeneratedBy' => $order->creator?->name ?? 'N/A',
            'status' => 'DISPATCHED',
            'remarks' => $log->notes ?: ($order->notes ?? ''),
            'totalOrderedQty' => $totalOrderedQty,
            'totalPrevDispatchedQty' => $totalPrevDispatchedQty,
            'totalDispatchedQty' => $totalDispatchedQty,
            'totalPendingQty' => $totalPendingQty,
            'totalAmount' => $totalAmount,
            'totalItems' => count($log->dispatchItems),
            'dispatchType' => ($totalPendingQty <= 0) ? 'Full Dispatch' : 'Partial Dispatch',
        ];

        $companyNameClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order->company?->name ?? 'COMPANY'));
        $dispatchDateStr = $log->created_at ? $log->created_at->format('d-m-Y') : now()->format('d-m-Y');
        $rawSt = strtoupper(trim(str_replace('_', '-', (string)($order->dispatch_status ?? 'DISPATCHED'))));
        $noteFilename = strtoupper($data['dispatchNo']) . '_' . strtoupper($data['orderNo']) . '_' . $companyNameClean . '_' . $rawSt . '_' . $dispatchDateStr . '.pdf';

        $pdf = Pdf::loadView('pdf.dispatch-note', $data)->setPaper('A4', 'portrait');

        return $pdf->download($noteFilename);
    }

    private function buildDispatchReportData(Request $request): array
    {
        $user = $this->authUser();
        [$from, $to] = $this->dateRange($request);
        
        $query = Order::with([
            'company',
            'transporter',
            'creator',
            'items.product',
            'dispatchLogs.dispatchItems.locationAllocations.location'
        ])->where('status', '!=', 'CANCELLED')->whereBetween('created_at', [$from, $to]);

        $q = $request->q;
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('company', function($qc) use ($q) {
                    $qc->where('name', 'like', "%{$q}%");
                })->orWhere('id', 'like', "%{$q}%")
                  ->orWhereHas('items.product', function($qp) use ($q) {
                      $qp->where('name', 'like', "%{$q}%");
                  });
            });
        }
        $companyId = $request->company_id;
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $statusFilter = $request->status;
        if ($statusFilter) {
            $target = strtoupper(trim(str_replace('_', ' ', $statusFilter)));
            $query->where(function($qo) use ($target) {
                if ($target === 'FULLY DISPATCHED' || $target === 'DONE') {
                    $qo->whereIn('dispatch_status', ['DONE', 'FULLY_DISPATCHED', 'FULLY DISPATCHED']);
                } elseif ($target === 'PARTIAL DISPATCH' || $target === 'PARTIAL') {
                    $qo->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIAL_PENDING', 'PARTIAL PENDING']);
                } elseif ($target === 'PENDING') {
                    $qo->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED', 'PARTIAL', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIAL_PENDING', 'PARTIAL PENDING'])
                       ->orWhereNull('dispatch_status');
                } else {
                    $qo->where('dispatch_status', 'like', "%{$target}%");
                }
            });
        }

        $orders = $query->orderBy('created_at', 'asc')->get();
        
        $rows = [];
        $totalQuantity = 0;
        $totalOrderedQty = 0;
        $totalPendingQty = 0;
        $totalValue = 0;
        $fullyDispatchedCount = 0;
        $partialDispatchCount = 0;
        $partialPendingCount = 0;
        $pendingCount = 0;
        $cancelledCount = 0;
        
        $customerSummaryMap = [];
        $productSummaryMap = [];

        $formatQty = fn($q, $u) => number_format($q, floor($q) == $q ? 0 : 2) . ' ' . $u;

        foreach ($orders as $order) {
            $rawSt = strtoupper(trim(str_replace('_', ' ', (string)($order->dispatch_status ?? 'PENDING'))));
            if ($order->status === 'CANCELLED') {
                $orderStatus = 'CANCELLED';
                $cancelledCount++;
            } elseif ($rawSt === 'DONE' || $rawSt === 'FULLY DISPATCHED') {
                $orderStatus = 'FULLY DISPATCHED';
                $fullyDispatchedCount++;
            } elseif ($rawSt === 'PARTIAL PENDING') {
                $orderStatus = 'PARTIAL PENDING';
                $partialPendingCount++;
            } elseif ($rawSt === 'PARTIAL' || $rawSt === 'PARTIAL DISPATCH') {
                $dispTotal = (float) $order->items->sum('dispatched_qty');
                $ordTotal = (float) $order->items->sum('quantity');
                if ($dispTotal >= $ordTotal && $ordTotal > 0) {
                    $orderStatus = 'FULLY DISPATCHED';
                    $fullyDispatchedCount++;
                } elseif ($dispTotal > 0) {
                    $orderStatus = 'PARTIAL DISPATCH';
                    $partialDispatchCount++;
                } else {
                    $orderStatus = 'PARTIAL PENDING';
                    $partialPendingCount++;
                }
            } else {
                $orderStatus = 'PENDING';
                $pendingCount++;
            }

            $orderItems = [];
            foreach ($order->items as $item) {
                $orderedQty = (float) $item->quantity;
                $dispatchedQty = (float) $item->dispatched_qty;
                $pendingQty = max(0, $orderedQty - $dispatchedQty);
                $rate = (float) $item->price;
                $amount = $orderedQty * $rate;
                $unit = strtoupper($item->product?->unit ?? 'KG');

                $totalOrderedQty += $orderedQty;
                $totalQuantity += $dispatchedQty;
                $totalPendingQty += $pendingQty;
                $totalValue += $amount;

                $locationsList = [];
                foreach ($order->dispatchLogs as $dlog) {
                    foreach ($dlog->dispatchItems as $di) {
                        if ($di->order_item_id == $item->id) {
                            foreach ($di->locationAllocations as $alloc) {
                                if (!empty($alloc->location?->name)) {
                                    $locationsList[] = strtoupper($alloc->location->name);
                                }
                            }
                        }
                    }
                }
                $locationsStr = !empty($locationsList) ? implode(', ', array_unique($locationsList)) : 'MAIN WAREHOUSE';
                $productFullName = strtoupper($item->product ? $item->product->formatName($item->grade) : 'UNKNOWN');

                $orderItems[] = [
                    'product' => $productFullName,
                    'grade' => strtoupper($item->grade ?? 'NONE'),
                    'locations' => $locationsStr,
                    'ordered_qty' => $orderedQty,
                    'ordered_qty_formatted' => $formatQty($orderedQty, $unit),
                    'qty' => $dispatchedQty,
                    'dispatch_qty_formatted' => $formatQty($dispatchedQty, $unit),
                    'pending_qty' => $pendingQty,
                    'pending_qty_formatted' => $formatQty($pendingQty, $unit),
                    'amount' => $amount,
                    'rate' => $rate,
                    'unit' => $unit,
                ];

                if (!isset($productSummaryMap[$productFullName])) {
                    $productSummaryMap[$productFullName] = ['product' => $productFullName, 'qty' => 0, 'count' => 0];
                }
                $productSummaryMap[$productFullName]['qty'] += $dispatchedQty > 0 ? $dispatchedQty : $orderedQty;
                $productSummaryMap[$productFullName]['count'] += 1;
            }

            if (!empty($orderItems)) {
                $orderDate = $order->created_at;
                $latestDispatchLog = $order->dispatchLogs->sortByDesc('created_at')->first();
                $dispatchDateStr = $latestDispatchLog ? $latestDispatchLog->created_at->format('d M Y') : '-';

                $nowDate = now();
                $diffDays = (int) $orderDate->copy()->startOfDay()->diffInDays($nowDate->copy()->startOfDay());
                $dueDaysText = $diffDays === 0 ? '0 Days' : $diffDays . ($diffDays === 1 ? ' Day' : ' Days');

                $custName = strtoupper($order->company?->name ?? 'N/A');

                $rows[] = [
                    'dispatch_id' => 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'order_id' => 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'order_date' => $orderDate->format('d M Y'),
                    'dispatch_date' => $dispatchDateStr,
                    'due_days' => $diffDays,
                    'due_days_text' => $dueDaysText,
                    'customer' => $custName,
                    'status' => $orderStatus,
                    'lr_copy' => null,
                    'items' => $orderItems,
                ];

                if (!isset($customerSummaryMap[$custName])) {
                    $customerSummaryMap[$custName] = ['customer' => $custName, 'qty' => 0, 'count' => 0];
                }
                $custDispatched = (float) $order->items->sum('dispatched_qty');
                $customerSummaryMap[$custName]['qty'] += $custDispatched > 0 ? $custDispatched : (float) $order->items->sum('quantity');
                $customerSummaryMap[$custName]['count'] += 1;
            }
        }

        $statusFilter = $request->status;
        $reportTitle = 'ALL DISPATCH HISTORY REPORT';
        if ($statusFilter) {
            $target = strtoupper(trim(str_replace('_', ' ', $statusFilter)));
            if ($target === 'PENDING') {
                $reportTitle = 'PENDING ORDERS HISTORY REPORT';
            } elseif ($target === 'PARTIAL DISPATCH' || $target === 'PARTIAL') {
                $reportTitle = 'PARTIAL ORDERS HISTORY REPORT';
            } elseif ($target === 'FULLY DISPATCHED' || $target === 'DONE') {
                $reportTitle = 'FULLY DISPATCH HISTORY REPORT';
            }
        }

        $isAllRange = ($request->range === 'all' || !$request->range) && !$request->from && !$request->start;

        return [
            'reportId' => 'RPT-DISP-' . now()->format('Ymd') . '-' . rand(100, 999),
            'userName' => $user['name'] ?? 'Authorized User',
            'generatedOn' => now()->format('d M Y, h:i A'),
            'isAllRange' => $isAllRange,
            'fromDate' => $isAllRange ? 'UP TO DATE' : ($from ? $from->format('d M Y') : 'UP TO DATE'),
            'toDate' => $to ? $to->format('d M Y') : now()->format('d M Y'),
            'reportTitle' => $reportTitle,
            'statusFilter' => strtoupper(trim(str_replace('_', ' ', (string)($statusFilter ?? '')))),
            'totalRecords' => count($rows),
            'completedCount' => $fullyDispatchedCount,
            'fullyDispatchedCount' => $fullyDispatchedCount,
            'partialDispatchCount' => $partialDispatchCount,
            'partialPendingCount' => $partialPendingCount,
            'pendingCount' => $pendingCount,
            'cancelledCount' => $cancelledCount,
            'totalQuantity' => $totalQuantity,
            'totalOrderedQty' => $totalOrderedQty,
            'totalPendingQty' => $totalPendingQty,
            'totalValue' => $totalValue,
            'rows' => $rows,
            'customerSummary' => array_values($customerSummaryMap),
            'productSummary' => array_values($productSummaryMap),
            'lrCopies' => [],
        ];
    }
}
