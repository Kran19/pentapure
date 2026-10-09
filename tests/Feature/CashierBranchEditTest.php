<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class CashierBranchEditTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'phone' => '+91 9876543210',
            'email' => 'admin@pentapure.com',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_editing_cashier_branch_updates_existing_transactions_and_cashiers()
    {
        $cashier1 = User::create([
            'name' => 'Cashier One',
            'username' => 'cashier1',
            'phone' => '+91 9876543211',
            'password' => Hash::make('password'),
            'role' => 'CASHIER',
            'branch' => 'Old Branch',
            'status' => 'ACTIVE',
        ]);

        $cashier2 = User::create([
            'name' => 'Cashier Two',
            'username' => 'cashier2',
            'phone' => '+91 9876543212',
            'password' => Hash::make('password'),
            'role' => 'CASHIER',
            'branch' => 'Old Branch',
            'status' => 'ACTIVE',
        ]);

        // Existing transactions under Old Branch
        $tx1 = Transaction::create([
            'user_id' => $cashier1->id,
            'type' => 'OUT',
            'amount' => 150.00,
            'category' => 'general',
            'site' => 'Old Branch',
        ]);

        $tx2 = Transaction::create([
            'user_id' => $cashier2->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'general',
            'site' => 'old branch', // lower-case variation
        ]);

        // Transaction with null site by cashier1
        $tx3 = Transaction::create([
            'user_id' => $cashier1->id,
            'type' => 'OUT',
            'amount' => 50.00,
            'category' => 'tea',
            'site' => null,
        ]);

        // Transaction belonging to another branch that should NOT be touched
        $txOther = Transaction::create([
            'user_id' => $this->adminUser->id,
            'type' => 'IN',
            'amount' => 1000.00,
            'category' => 'general',
            'site' => 'Different Branch',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        // Edit cashier1 and rename branch to 'New Modern Branch'
        $response = $this->withSession($session)->postJson('/admin/users', [
            'user_id' => $cashier1->id,
            'name' => 'Cashier One Updated',
            'username' => 'cashier1',
            'phone' => '+91 9876543211',
            'role' => 'CASHIER',
            'branch' => 'New Modern Branch',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert cashier1 branch updated
        $this->assertEquals('New Modern Branch', $cashier1->fresh()->branch);

        // Assert other cashier on Old Branch also updated to New Modern Branch
        $this->assertEquals('New Modern Branch', $cashier2->fresh()->branch);

        // Assert past transactions have site updated to New Modern Branch
        $this->assertEquals('New Modern Branch', $tx1->fresh()->site);
        $this->assertEquals('New Modern Branch', $tx2->fresh()->site);
        $this->assertEquals('New Modern Branch', $tx3->fresh()->site);

        // Assert unrelated transaction is untouched
        $this->assertEquals('Different Branch', $txOther->fresh()->site);

        // Assert old branch no longer exists in transactions
        $this->assertEquals(0, Transaction::where('site', 'Old Branch')->count());
        $this->assertEquals(0, Transaction::where('site', 'old branch')->count());
    }

    public function test_rename_branch_endpoint()
    {
        $cashier = User::create([
            'name' => 'Cashier Branch Test',
            'username' => 'cashier_bt',
            'phone' => '+91 9876543213',
            'password' => Hash::make('password'),
            'role' => 'CASHIER',
            'branch' => 'Factory Alpha',
            'status' => 'ACTIVE',
        ]);

        $tx = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 200.00,
            'category' => 'supplies',
            'site' => 'Factory Alpha',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->postJson('/admin/branches/rename', [
            'old_branch' => 'Factory Alpha',
            'new_branch' => 'Factory Beta Super',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('Factory Beta Super', $cashier->fresh()->branch);
        $this->assertEquals('Factory Beta Super', $tx->fresh()->site);
    }

    public function test_admin_users_view_provides_branches_list()
    {
        User::create([
            'name' => 'Cashier Branch View',
            'username' => 'cashier_bv',
            'phone' => '+91 9876543214',
            'password' => Hash::make('password'),
            'role' => 'CASHIER',
            'branch' => 'Unit 100',
            'status' => 'ACTIVE',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->get('/admin/users');
        $response->assertStatus(200);

        $pageData = $response->viewData('pageData');
        $this->assertArrayHasKey('branches', $pageData);
        $this->assertContains('Unit 100', $pageData['branches']);
    }
}
