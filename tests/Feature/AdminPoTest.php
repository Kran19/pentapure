<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPoTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $stockManagerUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->stockManagerUser = User::create([
            'name' => 'Stock Manager',
            'email' => 'sm@example.com',
            'password' => 'password123',
            'role' => 'STOCK_MANAGER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->product = Product::create([
            'name' => 'DEHYDRATED AMCHUR FLAKES (FG)',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);
    }

    public function test_pending_po_shows_only_mark_as_read_and_delete(): void
    {
        $po = PurchaseOrder::create([
            'user_id' => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity' => 5.0,
            'status' => 'PENDING',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->get('/admin/po');
        $response->assertStatus(200);

        // Shows Mark as Read button and Delete button
        $response->assertSee('adminApprovePO(' . $po->id . ', this)', false);
        $response->assertSee('adminDeletePO(' . $po->id . ')', false);

        // Does NOT show Mark as Order or Reject button
        $response->assertDontSee('adminOrderPO(' . $po->id . ', this)', false);
        $response->assertDontSee('adminRejectPO(' . $po->id . ', this)', false);
    }

    public function test_read_po_shows_mark_as_order_and_delete(): void
    {
        $po = PurchaseOrder::create([
            'user_id' => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity' => 5.0,
            'status' => 'READ',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->get('/admin/po');
        $response->assertStatus(200);

        // Shows Mark as Order button and Delete button
        $response->assertSee('adminOrderPO(' . $po->id . ', this)', false);
        $response->assertSee('adminDeletePO(' . $po->id . ')', false);

        // Does NOT show Mark as Read or Reject button
        $response->assertDontSee('adminApprovePO(' . $po->id . ', this)', false);
        $response->assertDontSee('adminRejectPO(' . $po->id . ', this)', false);
    }

    public function test_workflow_from_pending_to_read_to_ordered(): void
    {
        $po = PurchaseOrder::create([
            'user_id' => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity' => 5.0,
            'status' => 'PENDING',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        // 1. Initial State: PENDING -> Shows Mark as Read, not Mark as Order
        $response = $this->withSession($session)->get('/admin/po');
        $response->assertSee('adminApprovePO(' . $po->id . ', this)', false);
        $response->assertDontSee('adminOrderPO(' . $po->id . ', this)', false);

        // 2. Admin Marks as Read (approve)
        $approveResponse = $this->withSession($session)->postJson('/admin/po/approve', [
            'po_id' => $po->id,
        ]);
        $approveResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals('READ', $po->fresh()->status);

        // 3. Status is now READ -> Converts to Mark as Order button
        $responseAfterRead = $this->withSession($session)->get('/admin/po');
        $responseAfterRead->assertSee('adminOrderPO(' . $po->id . ', this)', false);
        $responseAfterRead->assertDontSee('adminApprovePO(' . $po->id . ', this)', false);

        // 4. Admin Marks as Order
        $orderResponse = $this->withSession($session)->postJson('/admin/po/order', [
            'po_id' => $po->id,
        ]);
        $orderResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals('ORDERED', $po->fresh()->status);

        // 5. Status is now ORDERED -> Neither Mark as Read nor Mark as Order button is shown, delete remains
        $responseAfterOrder = $this->withSession($session)->get('/admin/po');
        $responseAfterOrder->assertDontSee('adminApprovePO(' . $po->id . ', this)', false);
        $responseAfterOrder->assertDontSee('adminOrderPO(' . $po->id . ', this)', false);
        $responseAfterOrder->assertSee('adminDeletePO(' . $po->id . ')', false);
    }

    public function test_admin_po_table_displays_receive_date(): void
    {
        $po = PurchaseOrder::create([
            'user_id' => $this->stockManagerUser->id,
            'product_id' => $this->product->id,
            'quantity' => 120.0,
            'status' => 'RECEIVED',
            'date' => '2026-09-29 10:00:00',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->get('/admin/po');
        $response->assertStatus(200);
        $response->assertSee('Rec: 29-09-2026');
        $response->assertSee('swal-receive-date');
    }
}

