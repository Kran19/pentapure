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
}
