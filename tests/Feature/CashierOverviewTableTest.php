<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierOverviewTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_overview_table_structure_and_no_action_column(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $cashier = User::factory()->create(['role' => 'CASHIER', 'status' => 'ACTIVE', 'name' => 'John Cashier']);

        Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'IN',
            'amount' => 1000.00,
            'category' => 'sales',
            'note' => 'Daily auto entry',
            'created_at' => now()->subHours(2),
        ]);

        Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 200.00,
            'category' => 'edfd',
            'note' => 'Cash OUT',
            'created_at' => now()->subHour(),
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];
        $response = $this->withSession($session)->get('/admin/cashier-overview');

        $response->assertStatus(200);

        // Verify requested columns are present
        $response->assertSee('Date');
        $response->assertSee('Cashier');
        $response->assertSee('Type');
        $response->assertSee('Particulars / Note');
        $response->assertDontSee('<th style="padding:12px; text-align:left;">Category</th>', false);
        $response->assertSee('Amount');
        $response->assertSee('Balance');
        $response->assertSee('Bills');

        // Verify Action header is NOT present in the table
        $response->assertDontSee('<th>Action</th>', false);
        $response->assertDontSee('Action</th>', false);

        // Verify transaction content
        $response->assertSee('Daily auto entry');
        $response->assertSee('Cash OUT');
        $response->assertSee('John Cashier');
        $response->assertSee('No Bills');
        $response->assertSee('+₹1,000.00');
        $response->assertSee('-₹200.00');
        $response->assertSee('₹800.00');

        // Verify In: amount is styled in green (#16a34a)
        $response->assertSee('<span style="color:#16a34a; font-weight:600;">₹1,000.00</span>', false);

        // Verify status filter dropdown is present
        $response->assertSee('STATUS:');
        $response->assertSee('ALL (IN & OUT)', false);
        $response->assertSee('IN (CASH IN)');
        $response->assertSee('OUT (CASH OUT)');
    }

    public function test_cashier_overview_status_filtering(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $cashier = User::factory()->create(['role' => 'CASHIER', 'status' => 'ACTIVE', 'name' => 'John Cashier']);

        Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'IN',
            'amount' => 1000.00,
            'category' => 'sales',
            'note' => 'Income Transaction 123',
            'created_at' => now()->subHours(2),
        ]);

        Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 200.00,
            'category' => 'edfd',
            'note' => 'Expense Transaction 456',
            'created_at' => now()->subHour(),
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        // Filter by IN
        $responseIn = $this->withSession($session)->get('/admin/cashier-overview?type=IN');
        $responseIn->assertStatus(200);
        $responseIn->assertSee('Income Transaction 123');
        $responseIn->assertDontSee('Expense Transaction 456');
        $responseIn->assertSee('✕ Clear Filter');

        // Filter by OUT
        $responseOut = $this->withSession($session)->get('/admin/cashier-overview?type=OUT');
        $responseOut->assertStatus(200);
        $responseOut->assertSee('Expense Transaction 456');
        $responseOut->assertDontSee('Income Transaction 123');
        $responseOut->assertSee('✕ Clear Filter');

        // Filter by ALL (or empty)
        $responseAll = $this->withSession($session)->get('/admin/cashier-overview?type=');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Income Transaction 123');
        $responseAll->assertSee('Expense Transaction 456');

        // Combined filter: cashier_id + type
        $cashier2 = User::factory()->create(['role' => 'CASHIER', 'status' => 'ACTIVE', 'name' => 'Other Cashier']);
        Transaction::create([
            'user_id' => $cashier2->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
            'note' => 'Other Cashier Income',
            'created_at' => now()->subMinutes(10),
        ]);

        $responseCombined = $this->withSession($session)->get('/admin/cashier-overview?cashier_id=' . $cashier->id . '&type=IN');
        $responseCombined->assertStatus(200);
        $responseCombined->assertSee('Income Transaction 123');
        $responseCombined->assertDontSee('Expense Transaction 456');
        $responseCombined->assertDontSee('Other Cashier Income');
    }
}
