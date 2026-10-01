<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagerActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $stockManager;
    protected Product $product;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockManager = User::create([
            'name' => 'Stock Manager Test',
            'email' => 'sm_action_test@pentapure.com',
            'username' => 'sm_action_test',
            'password' => 'stock@123',
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $this->location = Location::firstOrCreate(['name' => 'Main Warehouse']);
        Location::firstOrCreate(['name' => 'Cold Room']);

        $this->product = Product::firstOrCreate([
            'name' => 'DEHYDRATED AMCHUR FLAKES (FG)',
        ], [
            'type' => 'FINISHED',
            'unit' => 'kg',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_stock_manager_action_page_loads_with_locations_and_action_type(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->get('/stock_manager/action');

        $response->assertStatus(200);
        $response->assertSee('Action Type *');
        $response->assertSee('Stock Inward');
        $response->assertSee('Stock Outward');
        $response->assertSee('SELECT LOCATIONS & QUANTITIES', false);
        $response->assertSee('sm-action-total-qty');
        $response->assertSee('Select Storage Location');
        $response->assertSee('sm-total-qty-input');
    }

    public function test_stock_locations_api_accessible_with_and_without_prefix(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'user_id' => $this->stockManager->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $this->location->id,
            'quantity' => 25.0,
            'transaction_type' => 'IN',
        ]);

        // With prefix
        $responsePrefixed = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->getJson("/stock_manager/api/stock/locations?product_id={$this->product->id}&stage=ALL&grade=ALL");

        $responsePrefixed->assertStatus(200);
        $responsePrefixed->assertJson(['success' => true]);
        $this->assertEquals(25.0, $responsePrefixed->json('breakdown.0.quantity'));

        // Without prefix (global route)
        $responseGlobal = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->getJson("/api/stock/locations?product_id={$this->product->id}&stage=ALL&grade=ALL");

        $responseGlobal->assertStatus(200);
        $responseGlobal->assertJson(['success' => true]);
        $this->assertEquals(25.0, $responseGlobal->json('breakdown.0.quantity'));
    }

    public function test_stock_manager_can_record_inward_and_calculate_total(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->postJson('/stock_manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'Main Warehouse', 'quantity' => 50.0],
            ],
            'notes' => 'Initial stock arrival',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(50.0, $this->product->totalAvailableStock());

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'transaction_type' => 'IN',
            'quantity' => 50.0,
            'grade' => 'NONE',
        ]);
    }

    public function test_stock_manager_can_record_outward_and_validates_insufficient_stock(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'user_id' => $this->stockManager->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $this->location->id,
            'quantity' => 20.0,
            'transaction_type' => 'IN',
        ]);

        // Attempt outward of 30 kg when only 20 kg available -> should fail with 400
        $failResponse = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->postJson('/stock_manager/outward', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'Main Warehouse', 'quantity' => 30.0],
            ],
        ]);

        $failResponse->assertStatus(400);
        $failResponse->assertJson(['success' => false]);

        // Attempt outward of 15 kg when 20 kg available -> should succeed
        $successResponse = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->postJson('/stock_manager/outward', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'Main Warehouse', 'quantity' => 15.0],
            ],
        ]);

        $successResponse->assertStatus(200);
        $successResponse->assertJson(['success' => true]);

        $this->assertEquals(5.0, $this->product->totalAvailableStock());
    }

    public function test_admin_and_stock_manager_can_export_stock_csv(): void
    {
        $admin = User::create([
            'name' => 'Admin Stock User',
            'email' => 'admin_stock_user@pentapure.com',
            'username' => 'admin_stock_user',
            'password' => 'admin@123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        // Insert some stock
        Stock::create([
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'A',
            'location_id' => $this->location->id,
            'quantity' => 50,
            'transaction_type' => 'IN',
            'user_id' => $admin->id,
        ]);

        // 1. Admin stock page has Export CSV button
        $pageResponse = $this->withSession([
            'auth_user' => $admin->toArray(),
        ])->get('/admin/stock');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Export CSV');
        $pageResponse->assertSee('adminExportStockCsv');

        // 2. Admin export CSV endpoint
        $csvResponse = $this->withSession([
            'auth_user' => $admin->toArray(),
        ])->post('/admin/stock/csv', [
            'stages' => 'FINISHED',
        ]);
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type'));

        // Capture streamed response content
        ob_start();
        $csvResponse->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('Stage', $csvContent);
        $this->assertStringContainsString('Product Name', $csvContent);
        $this->assertStringContainsString('DEHYDRATED AMCHUR FLAKES (FG)', $csvContent);
        $this->assertStringContainsString('Main Warehouse', $csvContent);
    }
}

