<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockAlphabeticalOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_alpha_test@pentapure.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->location = Location::firstOrCreate(['name' => 'Main Warehouse']);
    }

    public function test_admin_stock_page_and_new_products_are_sorted_alphabetically_a_to_z(): void
    {
        // Create 3 products out of alphabetical order
        // Zucchini has low stock (qty 5 <= threshold 50)
        $prodZ = Product::create([
            'name' => 'ZUCCHINI POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 200.0,
            'threshold' => 50.0,
            'is_active' => true,
        ]);
        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $this->location->id,
            'product_id' => $prodZ->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 5,
            'transaction_type' => 'IN',
        ]);

        // Mango has normal stock
        $prodM = Product::create([
            'name' => 'MANGO POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 150.0,
            'threshold' => 10.0,
            'is_active' => true,
        ]);
        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $this->location->id,
            'product_id' => $prodM->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 40,
            'transaction_type' => 'IN',
        ]);

        // Almond has normal stock
        $prodA = Product::create([
            'name' => 'ALMOND POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 300.0,
            'threshold' => 10.0,
            'is_active' => true,
        ]);
        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $this->location->id,
            'product_id' => $prodA->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 25,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        // On /admin/stock, products must appear strictly in alphabetical A to Z order:
        // ALMOND POWDER -> MANGO POWDER -> ZUCCHINI POWDER
        $posA = strpos($content, 'ALMOND POWDER');
        $posM = strpos($content, 'MANGO POWDER');
        $posZ = strpos($content, 'ZUCCHINI POWDER');

        $this->assertNotFalse($posA, 'ALMOND POWDER not found in stock view');
        $this->assertNotFalse($posM, 'MANGO POWDER not found in stock view');
        $this->assertNotFalse($posZ, 'ZUCCHINI POWDER not found in stock view');
        $this->assertTrue($posA < $posM, 'ALMOND POWDER must appear before MANGO POWDER');
        $this->assertTrue($posM < $posZ, 'MANGO POWDER must appear before ZUCCHINI POWDER (A to Z order)');

        // Now add a new product via storeProduct (e.g. BANANA POWDER)
        $newProdResponse = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/products', [
            'name' => 'BANANA POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 120.0,
        ]);
        $newProdResponse->assertStatus(200);
        $bananaId = $newProdResponse->json('product.id');

        // Add stock for the new product
        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $this->location->id,
            'product_id' => $bananaId,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 15,
            'transaction_type' => 'IN',
        ]);

        // Re-check /admin/stock
        $responseAfterNew = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock');

        $responseAfterNew->assertStatus(200);
        $contentAfterNew = $responseAfterNew->getContent();

        $posA2 = strpos($contentAfterNew, 'ALMOND POWDER');
        $posB2 = strpos($contentAfterNew, 'BANANA POWDER');
        $posM2 = strpos($contentAfterNew, 'MANGO POWDER');
        $posZ2 = strpos($contentAfterNew, 'ZUCCHINI POWDER');

        $this->assertTrue($posA2 < $posB2, 'ALMOND POWDER must appear before BANANA POWDER');
        $this->assertTrue($posB2 < $posM2, 'BANANA POWDER must appear before MANGO POWDER');
        $this->assertTrue($posM2 < $posZ2, 'MANGO POWDER must appear before ZUCCHINI POWDER');

        // Verify live stock API also returns A to Z
        $liveResponse = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/stock/live');
        $liveResponse->assertStatus(200);

        $liveNames = collect($liveResponse->json('data'))
            ->where('stage', 'FINISHED')
            ->pluck('name')
            ->values()
            ->toArray();

        $this->assertEquals(['ALMOND POWDER', 'BANANA POWDER', 'MANGO POWDER', 'ZUCCHINI POWDER'], $liveNames);
    }
}
