<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearStockDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_stock_data_command_clears_stocks_and_preserves_products_and_locations(): void
    {
        $adminUser = User::create([
            'name' => 'Stock Admin',
            'email' => 'stock_admin@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Polymer Grade X',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $initialLocationsCount = Location::count();
        $location = Location::first() ?: Location::create(['name' => 'Warehouse Alpha']);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $adminUser->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'quantity' => 500,
            'transaction_type' => 'IN',
            'location_id' => $location->id,
        ]);

        $this->assertDatabaseCount('stocks', 1);
        $this->assertDatabaseCount('products', 1);

        $this->artisan('stock:clear-data', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('stocks', 0);
        $this->assertDatabaseCount('products', 1);
        $this->assertEquals(Location::count(), Location::count());
    }

    public function test_admin_can_clear_live_stock_via_web_route(): void
    {
        $adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_stock_boss@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Sample Pipe',
            'type' => 'FINISHED',
            'unit' => 'm',
            'is_active' => true,
        ]);

        $location = Location::first() ?: Location::create(['name' => 'Main Warehouse']);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $adminUser->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 120,
            'transaction_type' => 'IN',
            'location_id' => $location->id,
        ]);

        $this->assertDatabaseCount('stocks', 1);

        $session = [
            'auth_user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
            ],
        ];

        $response = $this->withSession($session)
            ->post('/admin/stock/clear');

        $response->assertStatus(302);
        $this->assertDatabaseCount('stocks', 0);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_non_admin_cannot_clear_stock(): void
    {
        $salesUser = User::create([
            'name' => 'Sales Person',
            'email' => 'sales_guy@example.com',
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
            ->post('/admin/stock/clear');

        $response->assertStatus(403);
    }
}
