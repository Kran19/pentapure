<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUsageTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): array
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        return [
            'user' => $admin,
            'session' => ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']]
        ];
    }

    public function test_can_delete_unused_product(): void
    {
        $auth = $this->createAdmin();
        $product = Product::create([
            'name' => 'UNUSED TEST PRODUCT',
            'type' => 'RAW',
            'unit' => 'KG',
            'rate' => 10,
            'threshold' => 5,
        ]);

        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_cannot_delete_product_with_stock(): void
    {
        $auth = $this->createAdmin();
        $product = Product::create([
            'name' => 'PRODUCT WITH STOCK',
            'type' => 'RAW',
            'unit' => 'KG',
        ]);

        $loc = \App\Models\Location::create(['name' => 'Main Warehouse']);
        Stock::create([
            'product_id' => $product->id,
            'user_id' => $auth['user']->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'location_id' => $loc->id,
            'quantity' => 50,
            'transaction_type' => 'IN',
        ]);

        // Attempt API deletion
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Stock', $response->json('message'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        // Check UI shows disabled delete button
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee('Cannot delete: Product currently has 50.00 KG in Stock');
    }

    public function test_can_delete_product_with_grades_when_no_stock(): void
    {
        $auth = $this->createAdmin();
        $grade = Grade::create(['name' => '100 MESH', 'is_active' => true]);
        $product = Product::create([
            'name' => 'PRODUCT WITH GRADE NO STOCK',
            'type' => 'SEMI',
            'unit' => 'KG',
        ]);
        $product->grades()->attach($grade->id);

        // UI shows delete button enabled
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee("adminDeleteProduct({$product->id})");

        // Attempt API deletion -> succeeds!
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('grade_product', ['product_id' => $product->id]);
    }

    public function test_can_delete_product_when_net_stock_is_zero(): void
    {
        $auth = $this->createAdmin();
        $product = Product::create([
            'name' => 'PRODUCT ZERO STOCK',
            'type' => 'RAW',
            'unit' => 'KG',
        ]);

        $loc = \App\Models\Location::create(['name' => 'Main Warehouse']);
        // Net stock = 20 - 20 = 0 in admin/stock
        Stock::create([
            'product_id' => $product->id,
            'user_id' => $auth['user']->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'location_id' => $loc->id,
            'quantity' => 20,
            'transaction_type' => 'IN',
        ]);
        Stock::create([
            'product_id' => $product->id,
            'user_id' => $auth['user']->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'location_id' => $loc->id,
            'quantity' => 20,
            'transaction_type' => 'OUT',
        ]);

        // UI shows delete button enabled
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee("adminDeleteProduct({$product->id})");

        // Attempt API deletion -> succeeds!
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('stocks', ['product_id' => $product->id]);
    }

    public function test_stock_page_initial_stock_type_is_all(): void
    {
        $auth = $this->createAdmin();
        $response = $this->withSession($auth['session'])->get('/admin/stock');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('<option value="ALL" selected>ALL</option>', $content);
        $this->assertStringContainsString('<option value="RAW">RAW</option>', $content);
    }
}
