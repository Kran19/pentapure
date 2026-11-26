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

    public function test_ledger_pagination_structure_and_controls(): void
    {
        $cashier = User::create([
            'name' => 'Pagination Cashier',
            'email' => 'pag_cashier@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'branch' => 'MAIN',
        ]);

        // Create 15 transactions
        for ($i = 1; $i <= 15; $i++) {
            Transaction::create([
                'user_id' => $cashier->id,
                'type' => ($i % 2 === 0) ? 'OUT' : 'IN',
                'amount' => 100.00 * $i,
                'category' => 'sales',
                'site' => 'MAIN',
                'date' => now()->subMinutes(30 - $i),
                'created_at' => now()->subMinutes(30 - $i),
                'note' => "Transaction Note Number {$i}",
            ]);
        }

        $session = [
            'auth_user' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'role' => 'CASHIER',
                'branch' => 'MAIN',
            ]
        ];

        // 1. Default request should paginate to 10 entries
        $response = $this->withSession($session)->get('/cashier/ledger');
        $response->assertStatus(200);

        // Verify page-size dropdown
        $response->assertSee('id="ledger-page-size"', false);
        $response->assertSee('<option value="10"', false);
        $response->assertSee('selected>Show 10</option>', false);
        $response->assertSee('<option value="15"', false);
        $response->assertSee('Show 15</option>', false);

        // Verify pagination footer and info
        $response->assertSee('id="ledger-pagination-footer"', false);
        $response->assertSee('Showing 1 to 10 of 15 entries');

        // Verify newest 10 transactions (15 down to 6) are present in the table body on page 1
        preg_match('/<tbody id="ledger-tx-tbody">([\s\S]*?)<\/tbody>/', $response->getContent(), $m1);
        $tbodyP1 = $m1[1] ?? '';
        $this->assertStringContainsString('Transaction Note Number 15</div>', $tbodyP1);
        $this->assertStringContainsString('Transaction Note Number 6</div>', $tbodyP1);
        // Older transactions (5 down to 1) should not be on page 1 table rows
        $this->assertStringNotContainsString('Transaction Note Number 5</div>', $tbodyP1);
        $this->assertStringNotContainsString('Transaction Note Number 1</div>', $tbodyP1);

        // 2. Page 2 with per_page=10
        $responseP2 = $this->withSession($session)->get('/cashier/ledger?page=2&per_page=10');
        $responseP2->assertStatus(200);
        $responseP2->assertSee('Showing 11 to 15 of 15 entries');
        preg_match('/<tbody id="ledger-tx-tbody">([\s\S]*?)<\/tbody>/', $responseP2->getContent(), $m2);
        $tbodyP2 = $m2[1] ?? '';
        $this->assertStringContainsString('Transaction Note Number 5</div>', $tbodyP2);
        $this->assertStringContainsString('Transaction Note Number 1</div>', $tbodyP2);
        $this->assertStringNotContainsString('Transaction Note Number 15</div>', $tbodyP2);

        // 3. Page with per_page=15 (all 15 shown on 1 page)
        $responseAll = $this->withSession($session)->get('/cashier/ledger?per_page=15');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Showing 1 to 15 of 15 entries');
        preg_match('/<tbody id="ledger-tx-tbody">([\s\S]*?)<\/tbody>/', $responseAll->getContent(), $m3);
        $tbodyAll = $m3[1] ?? '';
        $this->assertStringContainsString('Transaction Note Number 15</div>', $tbodyAll);
        $this->assertStringContainsString('Transaction Note Number 1</div>', $tbodyAll);
    }
}
