<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockBulkAddTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Product $product;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pentapure.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->product = Product::create([
            'name' => 'GARLIC POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 150.00,
            'is_active' => true,
        ]);

        $this->location = Location::firstOrCreate(['name' => 'Warehouse A']);
        Location::firstOrCreate(['name' => 'Main Warehouse']);
    }

    public function test_admin_stock_page_has_proper_storage_location_dropdown(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must show Add Stock Entry card
        $this->assertStringContainsString('📦 Add Stock Entry', $content);
        $this->assertStringContainsString('bs-col-location', $content);
        $this->assertStringContainsString('bs-col-qty', $content);
        $this->assertStringContainsString('bs-loc-qty', $content);

        // Inside the stock form card, standard select dropdown for location must exist
        $cardStart = strpos($content, 'id="stock-form-card"');
        $this->assertNotFalse($cardStart);
        $cardEnd = strpos($content, 'id="btn-save-stock-card"', $cardStart);
        $this->assertNotFalse($cardEnd);
        $cardSnippet = substr($content, $cardStart, $cardEnd - $cardStart);

        $this->assertStringContainsString('Storage Location *', $cardSnippet);
        $this->assertStringContainsString('select class="form-control form-control-sm bs-location"', $cardSnippet);
        $this->assertStringContainsString('Main Warehouse', $cardSnippet);
        $this->assertStringContainsString('Warehouse A', $cardSnippet);
        // The old custom dropdown widget must NOT be present
        $this->assertStringNotContainsString('custom-location-dropdown', $cardSnippet);
    }

    public function test_bulk_add_stock_with_selected_location(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/stock/bulk-add', [
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'stage' => 'FINISHED',
                    'grade' => 'NONE',
                    'quantity' => 25.5,
                    'locations' => [
                        ['name' => 'Warehouse A', 'qty' => 25.5],
                    ],
                    'note' => 'Test location entry',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 25.5,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_bulk_add_stock_with_direct_quantity_fallback(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/stock/bulk-add', [
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'stage' => 'FINISHED',
                    'grade' => 'NONE',
                    'quantity' => 45.0,
                    'note' => 'Test direct qty entry',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $fallbackLocation = Location::orderBy('name')->first();
        $this->assertNotNull($fallbackLocation);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $fallbackLocation->id,
            'quantity' => 45.0,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_admin_stock_dropdown_only_shows_locations_from_database(): void
    {
        // Remove all locations and only create custom locations
        Stock::query()->delete();
        Location::query()->delete();
        Location::create(['name' => 'AC ROOM']);
        Location::create(['name' => 'COLD STORAGE']);

        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        $cardStart = strpos($content, 'id="stock-form-card"');
        $cardEnd = strpos($content, 'id="btn-save-stock-card"', $cardStart);
        $cardSnippet = substr($content, $cardStart, $cardEnd - $cardStart);

        $this->assertStringContainsString('AC ROOM', $cardSnippet);
        $this->assertStringContainsString('COLD STORAGE', $cardSnippet);
        $this->assertStringNotContainsString('Main Warehouse', $cardSnippet);
    }

    public function test_admin_stock_page_provides_master_locations_in_javascript(): void
    {
        $customLoc = Location::firstOrCreate(['name' => 'Custom Cold Storage']);

        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('window.adminMasterLocations =', $content);
        $this->assertStringContainsString('Custom Cold Storage', $content);
        $this->assertStringContainsString('swal-adj-loc', $content);
        $this->assertStringContainsString('swal-single-qty-input', $content);
    }

    public function test_adjust_stock_set_override_exact_quantity(): void
    {
        // Initial stock: 10 kg
        Stock::create([
            'product_id' => $this->product->id,
            'user_id' => $this->adminUser->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $this->location->id,
            'quantity' => 10.0,
            'transaction_type' => 'IN',
            'date' => now(),
            'notes' => 'Initial stock',
        ]);

        // Adjust stock with type 'set' to 35 kg
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/stock/adjust', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'adjust_type' => 'set',
            'location' => 'Warehouse A',
            'quantity' => 35.0,
            'reason' => 'Physical count correction',
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 35 - 10 = +25 IN
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 25.0,
            'transaction_type' => 'IN',
        ]);

        // Adjust stock with type 'set' to 15 kg
        $response2 = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/stock/adjust', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'adjust_type' => 'set',
            'location' => 'Warehouse A',
            'quantity' => 15.0,
            'reason' => 'Physical count correction',
            'date' => now()->toDateString(),
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // 15 - 35 = -20 OUT
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 20.0,
            'transaction_type' => 'OUT',
        ]);
    }
}

