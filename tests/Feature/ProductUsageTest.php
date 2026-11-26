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

    public function test_cannot_delete_product_with_pending_order(): void
    {
        $auth = $this->createAdmin();
        $company = \App\Models\Company::create([
            'name' => 'Pending Client Co',
            'email' => 'client@test.com',
            'phone' => '1234567890'
        ]);

        $product = Product::create([
            'name' => 'PRODUCT IN PENDING ORDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
        ]);

        $order = \App\Models\Order::create([
            'created_by' => $auth['user']->id,
            'company_id' => $company->id,
            'status' => 'OPEN',
            'dispatch_status' => 'PENDING',
            'total' => 500,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'grade' => 'NONE',
            'quantity' => 10,
            'price' => 50,
        ]);

        // Attempt deletion via API -> 422 blocked
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('pending Order', $response->json('message'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        // Check UI shows disabled button with pending order tooltip
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $ordCode = 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $uiResponse->assertSee("Cannot delete: Product is in use by pending Order #{$ordCode} (Sales / Dispatch)");
    }

    public function test_cannot_delete_product_with_order_history(): void
    {
        $auth = $this->createAdmin();
        $company = \App\Models\Company::create([
            'name' => 'History Client Co',
            'email' => 'history@test.com',
            'phone' => '1234567890'
        ]);

        $product = Product::create([
            'name' => 'PRODUCT IN ORDER HISTORY',
            'type' => 'FINISHED',
            'unit' => 'KG',
        ]);

        $order = \App\Models\Order::create([
            'created_by' => $auth['user']->id,
            'company_id' => $company->id,
            'status' => 'CLOSED',
            'dispatch_status' => 'DONE',
            'total' => 1000,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'grade' => 'NONE',
            'quantity' => 20,
            'price' => 50,
            'dispatched_qty' => 20,
        ]);

        // Attempt deletion via API -> 422 blocked to protect order history
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Order', $response->json('message'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        // Check UI shows disabled button with order history tooltip
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $ordCode = 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $uiResponse->assertSee("Cannot delete: Product is in use in Order #{$ordCode}");
    }

    public function test_cannot_delete_product_with_pending_purchase_order(): void
    {
        $auth = $this->createAdmin();
        $product = Product::create([
            'name' => 'PRODUCT WITH PENDING PO',
            'type' => 'RAW',
            'unit' => 'KG',
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'user_id' => $auth['user']->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'status' => 'PENDING',
        ]);

        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Purchase Order', $response->json('message'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        // Check UI
        $uiResponse = $this->withSession($auth['session'])->get('/admin/products');
        $uiResponse->assertStatus(200);
        $poCode = 'PO-' . str_pad($po->id, 4, '0', STR_PAD_LEFT);
        $uiResponse->assertSee("Cannot delete: Product has pending Purchase Order #{$poCode}");
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
