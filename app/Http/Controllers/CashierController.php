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

    public function profile()
    {
        return view('cashier.profile');
    }
}
