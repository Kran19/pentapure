<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearCashierDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_cashier_data_command_clears_transactions_and_preserves_users_and_categories(): void
    {
        $initialUserCount = User::count();
        $initialCategoryCount = Category::count();

        $cashierUser = User::create([
            'name' => 'Cashier Person',
            'email' => 'cashier_extra@example.com',
            'password' => 'secret123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $category = Category::create([
            'name' => 'Tea and Snacks',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $cashierUser->id,
            'type' => 'OUT',
            'amount' => 250,
            'category' => 'tea_and_snacks',
            'note' => 'Evening snacks for team',
            'date' => now(),
        ]);

        TransactionBill::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'bills/sample.jpg',
            'file_type' => 'image',
            'original_name' => 'sample.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_bills', 1);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertEquals($initialCategoryCount + 1, Category::count());

        $this->artisan('cashier:clear-data', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_bills', 0);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertEquals($initialCategoryCount + 1, Category::count());
    }

    public function test_admin_can_clear_cashier_ledger_via_web_route(): void
    {
        $adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_cashier@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $category = Category::create([
            'name' => 'Stationery',
            'is_active' => true,
        ]);

        $tx = Transaction::create([
            'user_id' => $adminUser->id,
            'type' => 'IN',
            'amount' => 5000,
            'category' => 'stationery',
            'date' => now(),
        ]);

        $this->assertDatabaseCount('transactions', 1);

        $session = [
            'auth_user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
            ],
        ];

        $response = $this->withSession($session)
            ->post('/cashier/ledger/clear');

        $response->assertStatus(302);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_non_admin_cannot_clear_cashier_ledger(): void
    {
        $cashierUser = User::create([
            'name' => 'Cashier Regular',
            'email' => 'cashier_reg@example.com',
            'password' => 'secret123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $session = [
            'auth_user' => [
                'id' => $cashierUser->id,
                'name' => $cashierUser->name,
                'role' => 'CASHIER',
                'status' => 'ACTIVE',
            ],
        ];

        $response = $this->withSession($session)
            ->post('/cashier/ledger/clear');

        $response->assertStatus(403);
    }
}
