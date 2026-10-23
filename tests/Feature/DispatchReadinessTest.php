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
        $this->assertStringContainsString('NOT READY', $content);
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
        $this->assertStringContainsString('READY TO PARTIAL DISPATCH', $content);
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
        $this->assertStringContainsString('NOT READY', $content);
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
        $this->assertStringContainsString('NOT READY', $content);
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

        // Filter: not_ready & not_dispatch
        $respNotDispatch = $this->withSession($session)->get('/sub_admin/dispatch/home?dispatch_filter=not_ready');
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

    public function test_orders_due_today_are_displayed_on_top(): void
    {
        $todayStr = now()->timezone('Asia/Kolkata')->toDateString();
        $upcomingStr = now()->timezone('Asia/Kolkata')->addDays(5)->toDateString();
        $overdueStr = now()->timezone('Asia/Kolkata')->subDays(2)->toDateString();

        // 1. Order due in 5 days (Upcoming)
        $orderUpcoming = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $upcomingStr,
            'created_at'      => now()->subMinutes(10),
        ]);
        OrderItem::create([
            'order_id'       => $orderUpcoming->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 10,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 2. Order without due date
        $orderNoDueDate = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 2000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => null,
            'created_at'      => now()->subMinutes(5),
        ]);
        OrderItem::create([
            'order_id'       => $orderNoDueDate->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 20,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 3. Order DUE TODAY (must be at the top!)
        $orderDueToday = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 3000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $todayStr,
            'created_at'      => now()->subDays(2),
        ]);
        OrderItem::create([
            'order_id'       => $orderDueToday->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 30,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 4. Order Overdue (due 2 days ago)
        $orderOverdue = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 4000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $overdueStr,
            'created_at'      => now()->subDays(4),
        ]);
        OrderItem::create([
            'order_id'       => $orderOverdue->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 40,
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

        // Must display the DUE TODAY highlight
        $this->assertStringContainsString('⚡ DUE TODAY', $content);
        $this->assertStringContainsString('Due today', $content);

        // Verify Order Due Today appears before the other orders in the HTML
        $posDueToday = strpos($content, "Order #{$orderDueToday->id}");
        $posOverdue  = strpos($content, "Order #{$orderOverdue->id}");
        $posUpcoming = strpos($content, "Order #{$orderUpcoming->id}");
        $posNoDue    = strpos($content, "Order #{$orderNoDueDate->id}");

        $this->assertNotFalse($posDueToday);
        $this->assertNotFalse($posOverdue);
        $this->assertNotFalse($posUpcoming);
        $this->assertNotFalse($posNoDue);

        // Due today MUST appear before overdue, upcoming, and no-due-date orders!
        $this->assertLessThan($posOverdue, $posDueToday, 'Order Due Today should appear before Overdue order');
        $this->assertLessThan($posUpcoming, $posDueToday, 'Order Due Today should appear before Upcoming order');
        $this->assertLessThan($posNoDue, $posDueToday, 'Order Due Today should appear before order without due date');
    }

    public function test_dispatch_report_displays_orders_due_today_on_top(): void
    {
        $todayStr = now()->timezone('Asia/Kolkata')->toDateString();
        $upcomingStr = now()->timezone('Asia/Kolkata')->addDays(3)->toDateString();
        $overdueStr = now()->timezone('Asia/Kolkata')->subDays(3)->toDateString();

        // 1. Upcoming order
        $orderUpcoming = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $upcomingStr,
            'created_at'      => now()->subMinutes(10),
        ]);
        OrderItem::create([
            'order_id'       => $orderUpcoming->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 10,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 2. Overdue order
        $orderOverdue = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 2000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $overdueStr,
            'created_at'      => now()->subDays(5),
        ]);
        OrderItem::create([
            'order_id'       => $orderOverdue->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 20,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 3. Due Today order (must be at the top)
        $orderDueToday = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 3000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'due_date'        => $todayStr,
            'created_at'      => now()->subDays(1),
        ]);
        OrderItem::create([
            'order_id'       => $orderDueToday->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 30,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $this->subAdminUser->update([
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'view_dispatch_report', 'module_dispatch'],
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'view_dispatch_report', 'module_dispatch'],
        ]];

        $response = $this->withSession($session)->get('/sub_admin/dispatch/report');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must display the DUE TODAY highlight
        $this->assertStringContainsString('⚡ DUE TODAY', $content);
        $this->assertStringContainsString('Due today', $content);

        // Verify Order Due Today appears before the other orders in the HTML
        $posDueToday = strpos($content, "Order #{$orderDueToday->id}");
        $posOverdue  = strpos($content, "Order #{$orderOverdue->id}");
        $posUpcoming = strpos($content, "Order #{$orderUpcoming->id}");

        $this->assertNotFalse($posDueToday);
        $this->assertNotFalse($posOverdue);
        $this->assertNotFalse($posUpcoming);

        // Due today MUST appear before overdue and upcoming orders!
        $this->assertLessThan($posOverdue, $posDueToday, 'Order Due Today should appear before Overdue order in report');
        $this->assertLessThan($posUpcoming, $posDueToday, 'Order Due Today should appear before Upcoming order in report');
    }

    public function test_cancelled_orders_do_not_appear_in_dispatch(): void
    {
        // 1. Active Order
        $activeOrder = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'created_at'      => now(),
        ]);
        $activeItem = OrderItem::create([
            'order_id'       => $activeOrder->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 10,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        // 2. Cancelled Order
        $cancelledOrder = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 2000,
            'status'          => 'CANCELLED',
            'dispatch_status' => 'PENDING',
            'created_at'      => now(),
        ]);
        $cancelledItem = OrderItem::create([
            'order_id'       => $cancelledOrder->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 20,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);

        $this->subAdminUser->update([
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'view_dispatch_report', 'module_dispatch'],
        ]);

        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'view_dispatch_report', 'module_dispatch'],
        ]];

        // Test 1: Dispatch Home
        $responseHome = $this->withSession($session)->get('/sub_admin/dispatch/home');
        $responseHome->assertStatus(200);
        $contentHome = $responseHome->getContent();
        $this->assertStringContainsString("Order #{$activeOrder->id}", $contentHome);
        $this->assertStringNotContainsString("Order #{$cancelledOrder->id}", $contentHome);

        // Test 2: Dispatch Action page (Dropdown)
        $responseAction = $this->withSession($session)->get('/sub_admin/dispatch/action');
        $responseAction->assertStatus(200);
        $contentAction = $responseAction->getContent();
        $this->assertStringContainsString("value=\"{$activeOrder->id}\"", $contentAction);
        $this->assertStringNotContainsString("value=\"{$cancelledOrder->id}\"", $contentAction);

        // Test 3: Dispatch Report page
        $responseReport = $this->withSession($session)->get('/sub_admin/dispatch/report');
        $responseReport->assertStatus(200);
        $contentReport = $responseReport->getContent();
        $this->assertStringContainsString("Order #{$activeOrder->id}", $contentReport);
        $this->assertStringNotContainsString("Order #{$cancelledOrder->id}", $contentReport);

        // Test 4: Order details endpoint rejects cancelled order with 404
        $responseDetails = $this->withSession($session)->get("/dispatch/order-details/{$cancelledOrder->id}");
        $responseDetails->assertStatus(404);

        // Test 5: storeDispatch rejects cancelled order with 422
        $responseStore = $this->withSession($session)->post('/sub_admin/dispatch/action', [
            'order_id' => $cancelledOrder->id,
            'items'    => [
                [
                    'order_item_id' => $cancelledItem->id,
                    'quantity'      => 5,
                ]
            ]
        ]);
        $responseStore->assertStatus(422);
    }

    public function test_partial_dispatch_order_action_page_and_details(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 3000,
            'status'          => 'CONFIRMED',
            'dispatch_status' => 'PARTIAL PENDING',
            'due_date'        => now()->timezone('Asia/Kolkata')->addDays(2)->toDateString(),
        ]);

        $item1 = OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'PREMIUM',
            'quantity'       => 10,
            'dispatched_qty' => 4,
            'price'          => 100,
        ]);

        $productFitting = Product::create([
            'name'      => 'PVC Fitting 90mm',
            'type'      => 'FINISHED',
            'unit'      => 'kg',
            'is_active' => true,
        ]);

        $item2 = OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $productFitting->id,
            'grade'          => 'NONE',
            'quantity'       => 20,
            'dispatched_qty' => 0,
            'price'          => 100,
        ]);

        $log = \App\Models\DispatchLog::create([
            'user_id'        => $this->subAdminUser->id,
            'order_id'       => $order->id,
            'transporter_id' => $this->transporter->id,
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log->id,
            'order_item_id'   => $item1->id,
            'quantity'        => 4,
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log->id,
            'order_item_id'   => $item2->id,
            'quantity'        => 5,
        ]);

        // Action page with query parameter
        $resp = $this->withSession($session)->get("/sub_admin/dispatch/action?order_id={$order->id}");
        $resp->assertStatus(200);
        $content = $resp->getContent();

        $this->assertStringContainsString("value=\"{$order->id}\"", $content);
        $this->assertStringContainsString("triggerAutoSelect", $content);
        $this->assertStringContainsString("\"remainingQty\":6", $content);
        $this->assertStringContainsString("\"remainingQty\":15", $content);

        // Order details endpoint
        $detailsResp = $this->withSession($session)->get("/sub_admin/dispatch/order-details/{$order->id}");
        $detailsResp->assertStatus(200);
        $detailsData = $detailsResp->json();

        $this->assertTrue($detailsData['success']);
        $this->assertEquals($order->id, $detailsData['order']['id']);
        $this->assertEquals(6, $detailsData['order']['items'][0]['remainingQty']);
        $this->assertEquals(15, $detailsData['order']['items'][1]['remainingQty']);
    }

    public function test_order_action_page_and_details_reflect_not_ready_when_zero_stock(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        $orderZero = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 1500,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $orderZero->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 50,
            'price'          => 30,
            'dispatched_qty' => 0,
        ]);

        // Action page should have [Not Ready] in option and NOT_READY in JSON
        $actionResp = $this->withSession($session)->get("/sub_admin/dispatch/action?order_id={$orderZero->id}");
        $actionResp->assertStatus(200);
        $content = $actionResp->getContent();

        $this->assertStringContainsString("value=\"{$orderZero->id}\"", $content);
        $this->assertStringContainsString("[Not Ready]", $content);
        $this->assertStringContainsString('"readiness":"NOT_READY"', $content);

        // Order details endpoint must also return readiness: NOT_READY
        $detailsResp = $this->withSession($session)->get("/sub_admin/dispatch/order-details/{$orderZero->id}");
        $detailsResp->assertStatus(200);
        $detailsData = $detailsResp->json();

        $this->assertTrue($detailsData['success']);
        $this->assertEquals('NOT_READY', $detailsData['order']['readiness']);
    }

    public function test_stock_added_from_admin_stock_shows_in_dispatch_home_and_action(): void
    {
        $adminUser = User::create([
            'name'        => 'Admin User',
            'email'       => 'admin@example.com',
            'password'    => 'password123',
            'role'        => 'ADMIN',
            'status'      => 'ACTIVE',
        ]);

        $adminSession = ['auth_user' => [
            'id'   => $adminUser->id,
            'name' => $adminUser->name,
            'role' => 'ADMIN',
        ]];

        $dispatchSession = ['auth_user' => [
            'id'   => $this->subAdminUser->id,
            'name' => $this->subAdminUser->name,
            'role' => 'SUB_ADMIN',
        ]];

        // 1. Admin adds stock via bulk-add (as sent by + Add Stock form on /admin/stock)
        $bulkResponse = $this->withSession($adminSession)->postJson('/admin/stock/bulk-add', [
            'items' => [
                [
                    'product_id' => $this->productPipe->id,
                    'stage'      => 'ALL', // as selected by default in admin stock form
                    'grade'      => 'NONE',
                    'quantity'   => 150,
                    'locations'  => [
                        ['name' => 'Main Warehouse', 'qty' => 150]
                    ],
                ]
            ]
        ]);
        $bulkResponse->assertStatus(200);
        $this->assertTrue($bulkResponse->json('success'));

        // 2. Location breakdown API returns stock when queried with stage=FINISHED or stage=FG
        $locRespFinished = $this->withSession($dispatchSession)->getJson("/api/stock/locations?product_id={$this->productPipe->id}&stage=FINISHED&grade=NONE");
        $locRespFinished->assertStatus(200);
        $this->assertTrue($locRespFinished->json('success'));
        $this->assertEquals(150, collect($locRespFinished->json('breakdown'))->firstWhere('name', 'Main Warehouse')['quantity'] ?? 0);

        $locRespFG = $this->withSession($dispatchSession)->getJson("/api/stock/locations?product_id={$this->productPipe->id}&stage=FG&grade=NONE");
        $locRespFG->assertStatus(200);
        $this->assertTrue($locRespFG->json('success'));
        $this->assertEquals(150, collect($locRespFG->json('breakdown'))->firstWhere('name', 'Main Warehouse')['quantity'] ?? 0);

        // 3. Create order for 100 kg
        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 6000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productPipe->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 60,
            'dispatched_qty' => 0,
        ]);

        // 4. Dispatch Home shows READY TO DISPATCH, 150 kg stock badge, and appears in stock tab
        $homeResp = $this->withSession($dispatchSession)->get('/sub_admin/dispatch/home');
        $homeResp->assertStatus(200);
        $content = $homeResp->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('READY TO DISPATCH', $content);
        $this->assertStringContainsString('150 kg', $content);

        // Stock tab view
        $stockTabResp = $this->withSession($dispatchSession)->get('/sub_admin/dispatch/home?main_tab=stock');
        $stockTabResp->assertStatus(200);
        $stockContent = $stockTabResp->getContent();
        $this->assertStringContainsString('HDPE Pipe 110mm', $stockContent);
        $this->assertStringContainsString('150', $stockContent);

        // 5. Dispatch Action page and Order Details API reflect READY_DISPATCH
        $actionResp = $this->withSession($dispatchSession)->get("/sub_admin/dispatch/action?order_id={$order->id}");
        $actionResp->assertStatus(200);
        $this->assertStringContainsString('"readiness":"READY_DISPATCH"', $actionResp->getContent());

        $detailsResp = $this->withSession($dispatchSession)->get("/sub_admin/dispatch/order-details/{$order->id}");
        $detailsResp->assertStatus(200);
        $this->assertEquals('READY_DISPATCH', $detailsResp->json('order.readiness'));
        $this->assertEquals(150, $detailsResp->json('order.items.0.availStock'));
    }
}


