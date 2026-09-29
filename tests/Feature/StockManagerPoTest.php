<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagerPoTest extends TestCase
{
    use RefreshDatabase;

    protected User $stockManagerUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockManagerUser = User::create([
            'name' => 'Stock Manager User',
            'email' => 'sm@example.com',
            'password' => 'password123',
            'role' => 'STOCK_MANAGER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->product = Product::create([
            'name' => 'Polymer Granules',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);
    }

    public function test_stock_manager_po_view_loads_with_smart_search_select2(): void
    {
        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['view_stock_manager_po'],
        ]];

        $response = $this->withSession($session)->get('/stock_manager/po');
        $response->assertStatus(200);

        // Check for smart search select2 integration elements
        $response->assertSee('po-select2-dropdown');
        $response->assertSee('initPoSelect2');
        $response->assertSee('po-avail-hint');
        $response->assertSee('po-unit-label');
        $response->assertSee('Polymer Granules');
    }

    public function test_stock_manager_can_create_purchase_order_with_notes(): void
    {
        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['view_stock_manager_po'],
        ]];

        $response = $this->withSession($session)->post('/stock_manager/po', [
            'product_id' => $this->product->id,
            'quantity'   => 150.5,
            'note'       => 'Urgent procurement for batch A-102',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'user_id'    => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity'   => 150.5,
            'note'       => 'Urgent procurement for batch A-102',
            'status'     => 'PENDING',
        ]);
    }

    public function test_stock_manager_po_creation_fails_without_product(): void
    {
        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['view_stock_manager_po'],
        ]];

        $response = $this->withSession($session)->post('/stock_manager/po', [
            'quantity' => 50,
        ]);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_stock_manager_can_mark_ordered_po_as_received_with_date(): void
    {
        $po = PurchaseOrder::create([
            'user_id'    => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity'   => 80,
            'note'       => 'Ordered from vendor',
            'status'     => 'ORDERED',
        ]);

        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['view_stock_manager_po'],
        ]];

        $response = $this->withSession($session)->postJson('/stock_manager/po/receive', [
            'po_id' => $po->id,
            'date'  => '2026-09-29',
            'note'  => 'Received full batch in good condition',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('purchase_orders', [
            'id'     => $po->id,
            'status' => 'RECEIVED',
            'note'   => 'Received full batch in good condition',
        ]);

        $po->refresh();
        $this->assertEquals('2026-09-29', $po->date->format('Y-m-d'));
    }

    public function test_stock_manager_po_table_displays_date_column_and_receive_date(): void
    {
        $po = PurchaseOrder::create([
            'user_id'    => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity'   => 120,
            'status'     => 'RECEIVED',
            'date'       => '2026-09-29 10:00:00',
        ]);

        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['view_stock_manager_po'],
        ]];

        $response = $this->withSession($session)->get('/stock_manager/po');
        $response->assertStatus(200);
        $response->assertSee('Rec: 29-09-2026');
        $response->assertSee('swal-receive-date');
    }
}
