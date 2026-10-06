<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductPackagingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_test',
            'phone' => '+91 9999000001',
            'password' => Hash::make('secret123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE'
        ]);
    }

    public function test_can_create_packaging_product(): void
    {
        $response = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->postJson('/admin/products', [
                'name' => 'CORRUGATED BOX 5 PLY',
                'type' => 'PACKAGING',
                'unit' => 'PCS',
                'rate' => 15.50,
                'threshold' => 100,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'name' => 'CORRUGATED BOX 5 PLY',
            'type' => 'PACKAGING',
            'unit' => 'PCS',
        ]);
    }

    public function test_can_update_product_from_raw_to_packaging_and_sync_stocks(): void
    {
        $location = \App\Models\Location::create(['name' => 'General Storage']);

        $product = Product::create([
            'name' => 'LAMINATION ROLL / WRAPPING ROLL',
            'type' => 'RAW',
            'unit' => 'KG',
            'rate' => 120.00,
            'threshold' => 50,
            'is_active' => true,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'location_id' => $location->id,
            'stage' => 'RAW',
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->postJson('/admin/products', [
                'product_id' => $product->id,
                'name' => 'LAMINATION ROLL / WRAPPING ROLL',
                'type' => 'PACKAGING',
                'unit' => 'KG',
                'rate' => 120.00,
                'threshold' => 50,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'type' => 'PACKAGING',
        ]);

        // Verify stock stage also synced to PACKAGING
        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'stage' => 'PACKAGING',
        ]);
    }

    public function test_packaging_product_appears_in_products_view(): void
    {
        Product::create([
            'name' => 'LAMINATION ROLL',
            'type' => 'PACKAGING',
            'unit' => 'KG',
            'is_active' => true,
        ]);

        $response = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get('/admin/products');

        $response->assertStatus(200);
        $response->assertSee('PACKAGING Materials');
        $response->assertSee('LAMINATION ROLL');
    }
}
