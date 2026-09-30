<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::create([
            'name'     => "Test {$role}",
            'username' => strtolower($role) . '_' . uniqid(),
            'email'    => strtolower($role) . '_' . uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => $role,
            'branch'   => 'Main Branch',
            'status'   => 'ACTIVE',
        ]);
    }

    public function test_sidebar_badges_hidden_when_counts_are_zero(): void
    {
        $admin = $this->createUser('ADMIN');
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $response = $this->withSession($session)->get('/admin/dashboard');
        $response->assertStatus(200);

        // Assert Purchase Requests and Dispatch Overview are present, but badges are not shown
        $response->assertSee('Purchase Requests');
        $response->assertSee('Dispatch Overview');
        $response->assertDontSee('pending purchase request(s)');
        $response->assertDontSee('pending dispatch order(s)');
    }

    public function test_sidebar_shows_badges_when_pending_operations_exist(): void
    {
        $admin = $this->createUser('ADMIN');
        $user  = $this->createUser('STOCK_MANAGER');
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $product = Product::create([
            'name'      => 'Test Product',
            'type'      => 'RAW',
            'threshold' => 10,
        ]);

        // 1. Create 2 Pending Purchase Orders
        PurchaseOrder::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 50,
            'status'     => 'PENDING',
        ]);
        PurchaseOrder::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 25,
            'status'     => 'PENDING',
        ]);

        // 2. Create 1 Pending Order for Dispatch
        $company = Company::create([
            'name'    => 'Acme Corp',
            'phone'   => '1234567890',
            'address' => 'Test address',
        ]);
        $transporter = \App\Models\Transporter::create([
            'name'    => 'Fast Transporter',
            'contact' => '9988776655',
        ]);
        Order::create([
            'created_by'      => $admin->id,
            'company_id'      => $company->id,
            'transporter_id'  => $transporter->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $response = $this->withSession($session)->get('/admin/dashboard');
        $response->assertStatus(200);

        // Check that Purchase Requests badge shows count 2
        $response->assertSee('sidebar-badge badge-danger');
        $response->assertSee('>2<', false);

        // Check that Dispatch Activity badge shows count 1
        $response->assertSee('sidebar-badge badge-warning');
        $response->assertSee('>1<', false);
    }
}
