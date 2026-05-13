<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    public function home()
    {
        $txs     = Transaction::where('user_id', $this->authUser()['id'])->orderByDesc('created_at')->get();
        $balance = $txs->sum(fn($t) => $t->type === 'IN' ? $t->amount : -$t->amount);

        $pageData = [
            'balance'      => $balance,
            'transactions' => $txs->map(fn($t) => [
                'id'        => $t->id,
                'type'      => $t->type,
                'amount'    => $t->amount,
                'category'  => $t->category,
                'note'      => $t->note,
                'reference' => $t->reference,
                'date'      => $t->created_at->toISOString(),
            ]),
        ];
        return view('cashier.home', compact('pageData'));
    }

    public function action()
    {
        return view('cashier.action');
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:IN,OUT',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $this->authUser();

        Transaction::create([
            'user_id'   => $user['id'],
            'type'      => $request->type,
            'amount'    => $request->amount,
            'category'  => $request->category ?? 'general',
            'note'      => $request->note,
            'reference' => $request->reference,
        ]);

        return response()->json(['success' => true, 'message' => "Transaction ({$request->type}) saved!"]);
    }

    public function history()
    {
        $txs = Transaction::where('user_id', $this->authUser()['id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => [
                'id'        => $t->id,
                'type'      => $t->type,
                'amount'    => $t->amount,
                'category'  => $t->category,
                'note'      => $t->note,
                'reference' => $t->reference,
                'date'      => $t->created_at->toISOString(),
            ]);

        $pageData = ['transactions' => $txs];
        return view('cashier.history', compact('pageData'));
    }

    public function downloadPdf(Request $request)
    {
        $user = $this->authUser();

        // Date range filtering (defaults to all time)
        $from = $request->from ? \Carbon\Carbon::parse($request->from)->startOfDay() : null;
        $to   = $request->to   ? \Carbon\Carbon::parse($request->to)->endOfDay()     : null;

        $query = Transaction::where('user_id', $user['id'])->orderByDesc('created_at');

        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);

        $txs = $query->get();

        $transactions = $txs->map(fn($t) => [
            'id'        => $t->id,
            'type'      => $t->type,
            'amount'    => $t->amount,
            'category'  => $t->category,
            'note'      => $t->note,
            'reference' => $t->reference,
            'date'      => $t->created_at->toISOString(),
        ])->toArray();

        $sumIn  = $txs->where('type', 'IN')->sum('amount');
        $sumOut = $txs->where('type', 'OUT')->sum('amount');

        $data = [
            'reportId'     => $user['id'] * 100 + rand(1, 99),
            'generatedOn'  => now()->format('d M Y'),
            'fromDate'     => $from ? $from->format('d M Y') : ($txs->last()?->created_at?->format('d M Y') ?? now()->format('d M Y')),
            'toDate'       => $to   ? $to->format('d M Y')   : now()->format('d M Y'),
            'cashierName'  => $user['name'],
            'cashierId'    => $user['id'],
            'transactions' => $transactions,
            'totalRecords' => count($transactions),
            'totalIn'      => $txs->where('type', 'IN')->count(),
            'totalOut'     => $txs->where('type', 'OUT')->count(),
            'sumIn'        => $sumIn,
            'sumOut'       => $sumOut,
            'balance'      => $sumIn - $sumOut,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-history', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('PentaPure_CashierReport_' . now()->format('Ymd_His') . '.pdf');
    }

    public function profile()
    {
        return view('cashier.profile');
    }
}
