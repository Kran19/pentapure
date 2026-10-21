<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $subAdminUser;
    protected Company $company;
    protected Transporter $transporter;
    protected Location $location;
    protected Product $productPipe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subAdminUser = User::create([
            'name'        => 'Sub Admin User',
            'email'       => 'subadmin@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'module_dispatch'],
        ]);

        $this->company = Company::create([
            'name'    => 'ALPHA CORP',
            'contact' => '+91 9876543210',
            'address' => 'Industrial Area',
        ]);

        $this->transporter = Transporter::create([
            'name'    => 'FAST FREIGHT',
            'contact' => '+91 9123456780',
        ]);

        $this->location = Location::create(['name' => 'Main Warehouse']);

        $this->productPipe = Product::create([
            'name'      => 'HDPE Pipe 110mm',
            'type'      => 'FINISHED',
            'unit'      => 'kg',
            'is_active' => true,
        ]);
    }

    public function test_order_shows_not_dispatch_when_stock_is_zero(): void
    {
        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('NOT DISPATCH', $content);
        $this->assertStringContainsString('STOCK:', $content);
        $this->assertStringContainsString('0 kg', $content);
        $this->assertStringContainsString('/sub_admin/dispatch/action', $content);
    }

    public function test_order_shows_partial_dispatch_when_some_stock_is_available(): void
    {
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'location_id'      => $this->location->id,
            'quantity'         => 40,
            'transaction_type' => 'IN',
        ]);

        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('PARTIAL DISPATCH', $content);
        $this->assertStringContainsString('40 kg', $content);
    }

    public function test_order_shows_ready_to_dispatch_when_quantity_is_fully_available(): void
    {
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'location_id'      => $this->location->id,
            'quantity'         => 100,
            'transaction_type' => 'IN',
        ]);

        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('READY TO DISPATCH', $content);
    }

    public function test_raw_stock_does_not_inflate_finished_dispatch_readiness(): void
    {
        // 500 kg in RAW stage, 0 kg in FINISHED stage
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'RAW',
            'grade'            => 'NONE',
            'location_id'      => $this->location->id,
            'quantity'         => 500,
            'transaction_type' => 'IN',
        ]);

        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('READY TO DISPATCH', $content);
        $this->assertStringContainsString('NOT DISPATCH', $content);
    }

    public function test_different_grade_does_not_satisfy_specific_grade_order(): void
    {
        // Stock is Grade B, Order is Grade A
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'FINISHED',
            'grade'            => 'B',
            'location_id'      => $this->location->id,
            'quantity'         => 200,
            'transaction_type' => 'IN',
        ]);

        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'A',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('READY TO DISPATCH', $content);
        $this->assertStringContainsString('NOT DISPATCH', $content);
    }

    public function test_dispatch_filters_work_with_scenarios(): void
    {
        // Order 1: Ready to dispatch (has stock)
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'location_id'      => $this->location->id,
            'quantity'         => 100,
            'transaction_type' => 'IN',
        ]);
        $order1 = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $order1->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // Order 2: Not dispatch (0 stock)
        $product2 = Product::create([
            'name'      => 'PVC Pipe 50mm',
            'type'      => 'FINISHED',
            'unit'      => 'kg',
            'is_active' => true,
        ]);
        $order2 = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 3000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $order2->id,
            'product_id'     => $product2->id,
            'grade'          => 'NONE',
            'quantity'       => 50,
            'price'          => 60,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        // Filter: not_dispatch
        $respNotDispatch = $this->withSession($session)->get('/sub_admin/dispatch/home?dispatch_filter=not_dispatch');
        $respNotDispatch->assertStatus(200);
        $respNotDispatchContent = $respNotDispatch->getContent();
        $this->assertStringContainsString("Order #{$order2->id}", $respNotDispatchContent);
        $this->assertStringNotContainsString("Order #{$order1->id}", $respNotDispatchContent);

        // Filter: ready_dispatch
        $respReady = $this->withSession($session)->get('/sub_admin/dispatch/home?dispatch_filter=ready_dispatch');
        $respReady->assertStatus(200);
        $respReadyContent = $respReady->getContent();
        $this->assertStringContainsString("Order #{$order1->id}", $respReadyContent);
        $this->assertStringNotContainsString("Order #{$order2->id}", $respReadyContent);
    }

    public function test_ready_to_dispatch_button_links_directly_to_sub_admin_dispatch_action(): void
    {
        Stock::create([
            'product_id'       => $this->productPipe->id,
            'user_id'          => $this->subAdminUser->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'location_id'      => $this->location->id,
            'quantity'         => 100,
            'transaction_type' => 'IN',
        ]);
        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must link directly to /sub_admin/dispatch/action?order_id=...
        $expectedUrl = "/sub_admin/dispatch/action?order_id={$order->id}";
        $this->assertStringContainsString($expectedUrl, $content);
        $this->assertStringContainsString('READY TO DISPATCH', $content);

        // Also test opening the action page with the order_id query param
        $actionResp = $this->withSession($session)->get("/sub_admin/dispatch/action?order_id={$order->id}");
        $actionResp->assertStatus(200);
        $actionContent = $actionResp->getContent();
        $this->assertStringContainsString("order_id", $actionContent);
        $this->assertStringContainsString("window.currentPendingOrders", $actionContent);
    }
}

