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
    }

    public function test_admin_stock_page_does_not_show_storage_location_in_add_stock_entry(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must show Add Stock Entry card
        $this->assertStringContainsString('📦 Add Stock Entry', $content);
        $this->assertStringContainsString('bs-col-qty', $content);
        $this->assertStringContainsString('bs-loc-qty', $content);

        // Inside the stock form card, "Storage Location *" must NOT show
        $cardStart = strpos($content, 'id="stock-form-card"');
        $this->assertNotFalse($cardStart);
        $cardEnd = strpos($content, 'id="btn-save-stock-card"', $cardStart);
        $this->assertNotFalse($cardEnd);
        $cardSnippet = substr($content, $cardStart, $cardEnd - $cardStart);

        $this->assertStringNotContainsString('Storage Location *', $cardSnippet);
        $this->assertStringNotContainsString('custom-location-dropdown', $cardSnippet);
    }

    public function test_bulk_add_stock_with_quantity_defaults_to_main_warehouse(): void
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
                    'quantity' => 45.5,
                    'note' => 'Test direct qty entry',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $mainWarehouse = Location::where('name', 'Main Warehouse')->first();
        $this->assertNotNull($mainWarehouse);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $mainWarehouse->id,
            'quantity' => 45.5,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_bulk_add_stock_with_locations_array(): void
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
                    'locations' => [
                        ['name' => 'Main Warehouse', 'qty' => 30.0],
                    ],
                    'note' => 'Test location array entry',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $mainWarehouse = Location::where('name', 'Main Warehouse')->first();
        $this->assertNotNull($mainWarehouse);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'location_id' => $mainWarehouse->id,
            'quantity' => 30.0,
            'transaction_type' => 'IN',
        ]);
    }
}
