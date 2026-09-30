<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_multiple_stock_units_properly(): void
    {
        $admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin_mgr@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $loc = Location::create(['name' => 'Warehouse Alpha']);

        // Create Raw products with different units
        $rawKg = Product::create([
            'name' => 'Polymer Granules',
            'type' => 'RAW',
            'unit' => 'KG',
            'is_active' => true,
        ]);

        $rawPcs = Product::create([
            'name' => 'Caps & Closures',
            'type' => 'RAW',
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        // Add inward stock for both
        Stock::create([
            'product_id' => $rawKg->id,
            'user_id' => $admin->id,
            'stage' => 'RAW',
            'location_id' => $loc->id,
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);

        Stock::create([
            'product_id' => $rawPcs->id,
            'user_id' => $admin->id,
            'stage' => 'RAW',
            'location_id' => $loc->id,
            'quantity' => 50,
            'transaction_type' => 'IN',
        ]);

        // Finished Goods with multiple units
        $fgKg = Product::create([
            'name' => 'Finished Water Bottle Pack',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'is_active' => true,
        ]);

        $fgBox = Product::create([
            'name' => 'Finished Filter Kits',
            'type' => 'FINISHED',
            'unit' => 'BOX',
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $fgKg->id,
            'user_id' => $admin->id,
            'stage' => 'FINISHED',
            'location_id' => $loc->id,
            'quantity' => 1300,
            'transaction_type' => 'IN',
        ]);

        Stock::create([
            'product_id' => $fgBox->id,
            'user_id' => $admin->id,
            'stage' => 'FINISHED',
            'location_id' => $loc->id,
            'quantity' => 40,
            'transaction_type' => 'IN',
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $response = $this->withSession($session)->get('/admin/home');
        $response->assertStatus(200);

        // Verify units and quantities appear for RAW
        $response->assertSee('200.0');
        $response->assertSee('KG');
        $response->assertSee('50');
        $response->assertSee('PCS');
        $response->assertSee('Raw Stock');

        // Verify FG stock units
        $response->assertSee('1,300.0');
        $response->assertSee('40');
        $response->assertSee('BOX');
        $response->assertSee('FG Stock');

        // Verify hardcoded misleading (kg) is removed from labels
        $response->assertDontSee('Raw Stock (kg)');
        $response->assertDontSee('Semi Stock (kg)');
        $response->assertDontSee('FG Stock (kg)');
    }
}
