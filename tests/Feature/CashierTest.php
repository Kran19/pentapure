<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierA;
    protected User $cashierB;
    protected User $cashierC;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Office Supplies', 'is_active' => true]);

        $this->cashierB = User::create([
            'name' => 'Cashier B',
            'email' => 'cashierb@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->cashierC = User::create([
            'name' => 'Cashier C',
            'email' => 'cashierc@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        // Cashier A can see Cashier B, but NOT Cashier C
        $this->cashierA = User::create([
            'name' => 'Cashier A',
            'email' => 'cashiera@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'visible_cashiers' => [$this->cashierB->id],
        ]);
    }

    public function test_cashier_can_create_and_edit_own_transaction(): void
    {
        $session = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [
                [
                    'type' => 'OUT',
                    'amount' => 150.00,
                    'category' => 'office_supplies',
                    'note' => 'Stationery purchase',
                ]
            ]
        ]);

        $response->assertJson(['success' => true]);

        $tx = Transaction::where('user_id', $this->cashierA->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(150.00, (float) $tx->amount);

        // Edit transaction
        $editResponse = $this->withSession($session)->putJson("/cashier/action/{$tx->id}", [
            'amount' => 200.00,
            'category' => 'office_supplies',
            'note' => 'Updated stationery purchase',
        ]);

        $editResponse->assertJson(['success' => true]);

        $tx->refresh();
        $this->assertEquals(200.00, (float) $tx->amount);

        // Verify audit log entry
        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $tx->id,
            'user_id' => $this->cashierA->id,
            'action' => 'EDITED',
        ]);
    }

    public function test_cashier_cannot_edit_another_cashiers_transaction(): void
    {
        $txB = Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);

        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($sessionA)->putJson("/cashier/action/{$txB->id}", [
            'amount' => 1000.00,
            'category' => 'sales',
        ]);

        $response->assertStatus(404);
    }

    public function test_team_ledger_respects_visible_cashiers_permissions(): void
    {
        // Create transactions for Cashier B and Cashier C
        Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);

        Transaction::create([
            'user_id' => $this->cashierC->id,
            'type' => 'IN',
            'amount' => 9999.00,
            'category' => 'sales',
        ]);

        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($sessionA)->get('/cashier/ledger');
        $response->assertStatus(200);

        $pageData = $response->viewData('pageData');
        $teamTxs = collect($pageData['teamTransactions']);

        // Cashier A sees Cashier B's transaction ($500)
        $this->assertTrue($teamTxs->pluck('amount')->contains(500.00));

        // Cashier A DOES NOT see Cashier C's transaction ($9999)
        $this->assertFalse($teamTxs->pluck('amount')->contains(9999.00));
    }
}
