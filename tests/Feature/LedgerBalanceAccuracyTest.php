<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerBalanceAccuracyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_balance_calculation_accuracy(): void
    {
        $cashier = User::create([
            'name' => 'Cashier Three',
            'email' => 'cashier3@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'branch' => 'pPF OTHER expenses',
        ]);

        // Tx 1: Initial Deposit ₹10,000 on 2026-10-20
        $t1 = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'IN',
            'amount' => 10000.00,
            'category' => 'capital',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-20 10:00:00',
            'created_at' => '2026-10-20 10:00:00',
            'note' => 'Opening Cash',
        ]);

        // Tx 2: Expense ₹2,000 on 2026-10-21 (same date/time as Tx 3 & 4)
        $t2 = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 2000.00,
            'category' => 'other_expenses',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-21 12:00:00',
            'created_at' => '2026-10-21 12:00:00',
            'note' => 'Supplies',
        ]);

        // Tx 3: Expense ₹1,500 on 2026-10-21
        $t3 = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 1500.00,
            'category' => 'other_expenses',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-21 12:00:00',
            'created_at' => '2026-10-21 12:00:00',
            'note' => 'Courier',
        ]);

        // Tx 4: Income ₹5,000 on 2026-10-21
        $t4 = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'IN',
            'amount' => 5000.00,
            'category' => 'sales',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-21 12:00:00',
            'created_at' => '2026-10-21 12:00:00',
            'note' => 'Cash Sale',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'role' => 'CASHIER',
                'branch' => 'pPF OTHER expenses',
            ]
        ])->get('/cashier/ledger');

        $response->assertStatus(200);

        $html = $response->getContent();
        
        // Assert balances match in reverse-chronological order (newest first):
        // Tx 4 (+5,000) => 11,500.00
        // Tx 3 (-1,500) => 6,500.00
        // Tx 2 (-2,000) => 8,000.00
        // Tx 1 (+10,000) => 10,000.00
        preg_match_all('/<td style="padding:12px; font-weight:bold; color:[^>]*; text-align:right; white-space:nowrap;">\s*(₹[0-9,\.\-]+)\s*<\/td>/i', $html, $matches);
        
        $this->assertEquals(['₹11,500.00', '₹6,500.00', '₹8,000.00', '₹10,000.00'], $matches[1]);
    }
}
