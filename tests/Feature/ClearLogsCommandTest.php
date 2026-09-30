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

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(storage_path('app/admin_logs_cleared_at.txt'));
        Cache::forget('admin_logs_cleared_at');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/admin_logs_cleared_at.txt'));
        Cache::forget('admin_logs_cleared_at');
        parent::tearDown();
    }

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
        $this->assertNotNull(\App\Http\Controllers\AdminController::getLogsClearedAt());

        // File persistence survives cache flush
        Cache::flush();
        $this->assertNull(Cache::get('admin_logs_cleared_at'));
        $this->assertNotNull(\App\Http\Controllers\AdminController::getLogsClearedAt());
    }

    public function test_admin_logs_clear_web_route_clears_logs_and_shows_empty_state(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Finished Starch',
            'type' => 'FINISHED',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        ProductionLog::create([
            'user_id' => $admin->id,
            'type' => 'FINISHED',
            'output_product_id' => $product->id,
            'output_grade' => 'NONE',
            'output_qty' => 50,
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        // Ensure production log is currently visible
        $responseBefore = $this->withSession($session)->get('/admin/logs');
        $responseBefore->assertStatus(200);
        $responseBefore->assertSee('FINISHED STARCH');

        // Clear logs via POST web endpoint
        $clearResponse = $this->withSession($session)->post('/admin/logs/clear');
        $clearResponse->assertRedirect();
        $clearResponse->assertSessionHas('success');

        // Check logs view after clear
        $responseAfter = $this->withSession($session)->get('/admin/logs');
        $responseAfter->assertStatus(200);
        $responseAfter->assertDontSee('FINISHED STARCH');
        $responseAfter->assertSee('No activity logs found.');

        // Verify products and users are unaffected
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}

