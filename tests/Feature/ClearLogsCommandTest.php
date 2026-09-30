<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClearLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_logs_command_clears_production_logs_and_sets_cutoff_without_losing_stock(): void
    {
        $initialUserCount = User::count();
        $initialProductCount = Product::count();

        $user = User::create([
            'name' => 'Operator',
            'email' => 'op@example.com',
            'password' => 'secret123',
            'role' => 'RAW',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Raw Starch',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Main Godown',
        ]);

        // Create a production log
        ProductionLog::create([
            'user_id' => $user->id,
            'type' => 'FINISHED',
            'output_product_id' => $product->id,
            'output_grade' => 'NONE',
            'output_qty' => 100,
        ]);

        // Create a stock transaction (inventory)
        Stock::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'stage' => 'RAW',
            'location_id' => $location->id,
            'quantity' => 500,
            'transaction_type' => 'IN',
            'notes' => 'Initial batch',
        ]);

        $this->assertDatabaseCount('production_logs', 1);
        $this->assertDatabaseCount('stocks', 1);

        $this->artisan('logs:clear-all', ['--force' => true])
            ->assertExitCode(0);

        // Production logs are cleared
        $this->assertDatabaseCount('production_logs', 0);

        // Stock inventory is 100% PRESERVED!
        $this->assertDatabaseCount('stocks', 1);

        // Users and products are 100% PRESERVED!
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertEquals($initialProductCount + 1, Product::count());

        // Cache cutoff is set
        $this->assertNotNull(Cache::get('admin_logs_cleared_at'));
    }
}
