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
        $response->assertSee('Details');
        $response->assertSee('Category');
        $response->assertSee('Amount');
        $response->assertSee('Balance');
        $response->assertSee('Bills');

        // Verify Action header is NOT present in the table
        $response->assertDontSee('<th>Action</th>', false);
        $response->assertDontSee('Action</th>', false);

        // Verify transaction content
        $response->assertSee('Daily auto entry');
        $response->assertSee('Cash OUT');
        $response->assertSee('EDFD');
        $response->assertSee('SALES');
        $response->assertSee('John Cashier');
        $response->assertSee('No Bills');
        $response->assertSee('+₹1,000.00');
        $response->assertSee('-₹200.00');
        $response->assertSee('₹800.00');
    }
}
