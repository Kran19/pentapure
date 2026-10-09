<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearSalesDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_sales_data_command_clears_orders_and_preserves_users_and_companies(): void
    {
        $initialUserCount = User::count();

        $salesUser = User::create([
            'name' => 'Sales Person Extra',
            'email' => 'sales_extra@example.com',
            'password' => 'secret123',
            'role' => 'SALES',
            'status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'name' => 'Acme Corporation',
            'contact' => '9876543210',
        ]);

        $transporter = Transporter::create([
            'name' => 'Fast Logistics',
            'contact' => '9123456780',
        ]);

        $product = Product::create([
            'name' => 'Sample Polymer',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $order = Order::create([
            'created_by' => $salesUser->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 5000,
            'status' => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'grade' => 'NONE',
            'quantity' => 100,
            'price' => 50,
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('transporters', 1);

        $this->artisan('sales:clear-data', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('transporters', 1);
    }

    public function test_admin_can_clear_sales_and_dispatch_history_via_web_route(): void
    {
        $adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_boss@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'name' => 'Beta Corp',
            'contact' => '9998887776',
        ]);

        $order = Order::create([
            'created_by' => $adminUser->id,
            'company_id' => $company->id,
            'total' => 12000,
            'status' => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $dispatchLog = \App\Models\DispatchLog::create([
            'user_id' => $adminUser->id,
            'order_id' => $order->id,
            'lr_no' => 'LR-9988',
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('dispatch_logs', 1);

        $session = [
            'auth_user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
            ],
        ];

        // Call web clear endpoint
        $response = $this->withSession($session)
            ->post('/sales/history/clear');

        $response->assertStatus(302);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('dispatch_logs', 0);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_non_admin_cannot_clear_sales_and_dispatch_history(): void
    {
        $salesUser = User::create([
            'name' => 'Regular Sales',
            'email' => 'reg_sales@example.com',
            'password' => 'secret123',
            'role' => 'SALES',
            'status' => 'ACTIVE',
        ]);

        $session = [
            'auth_user' => [
                'id' => $salesUser->id,
                'name' => $salesUser->name,
                'role' => 'SALES',
                'status' => 'ACTIVE',
            ],
        ];

        $response = $this->withSession($session)
            ->post('/sales/history/clear');

        $response->assertStatus(403);
    }
}
