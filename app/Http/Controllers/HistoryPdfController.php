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

        $data = $this->buildReportData($request, $panel);
        $pdf = Pdf::loadView('pdf.history-report', $data)->setPaper('A4', 'portrait');

        return $pdf->download('PentaPure_' . ucfirst(strtolower($panel)) . '_History_' . now()->format('Ymd_His') . '.pdf');
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
            'totalRecords' => count($rows),
            'completed' => $completed,
            'pending' => $pending,
            'approved' => collect($rows)->whereIn('status', ['APPROVED', 'DONE', 'CLOSED'])->count(),
            'amountTotal' => $amountTotal,
        ];
    }

    private function dateRange(Request $request): array
    {
        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->to ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    private function rawRows(Carbon $from, Carbon $to): array
    {
        return Stock::with('product')->where('stage', 'RAW')->where('transaction_type', 'IN')->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn ($s) => [
                'id' => 'RAW-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                'type' => 'Raw Inward',
                'date' => $s->created_at->format('d M Y'),
                'status' => 'COMPLETED',
                'amount' => 0,
                'description' => ($s->product?->name ?? 'Material') . ' - ' . $s->quantity . ' ' . ($s->product?->unit ?? 'kg') . ' (' . $s->grade . ')',
            ])->toArray();
    }

    private function productionRows(string $type, Carbon $from, Carbon $to): array
    {
        return ProductionLog::with(['outputProduct', 'inputs.inputProduct'])->where('type', $type)->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn ($l) => [
                'id' => $type . '-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                'type' => ucfirst(strtolower($type)) . ' Production',
                'date' => $l->created_at->format('d M Y'),
                'status' => 'COMPLETED',
                'amount' => 0,
                'description' => 'Produced ' . $l->output_qty . ' kg ' . ($l->outputProduct?->name ?? 'Product') . ' (' . $l->output_grade . ')',
            ])->toArray();
    }

    private function salesRows(Carbon $from, Carbon $to): array
    {
        return Order::with('company')->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn ($o) => [
                'id' => 'ORD-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'type' => 'Order',
                'date' => $o->created_at->format('d M Y'),
                'status' => $o->status,
                'amount' => (float) $o->total,
                'description' => ($o->company?->name ?? 'Company') . ' - Dispatch ' . $o->dispatch_status,
            ])->toArray();
    }

    private function dispatchRows(Carbon $from, Carbon $to): array
    {
        return DispatchLog::with(['order.company', 'dispatchItems'])->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn ($d) => [
                'id' => 'DSP-' . str_pad($d->id, 4, '0', STR_PAD_LEFT),
                'type' => 'Dispatch',
                'date' => $d->created_at->format('d M Y'),
                'status' => $d->lr_image_path ? 'DONE' : 'PENDING',
                'amount' => (float) ($d->order?->total ?? 0),
                'description' => 'Order #' . $d->order_id . ' - ' . ($d->order?->company?->name ?? 'Company') . ' - ' . $d->dispatchItems->sum('quantity') . ' kg',
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
                'description' => trim(($t->category ?? 'General') . ' - ' . ($t->note ?: $t->reference)),
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
}
