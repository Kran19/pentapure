<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $dispatchUser;
    protected Company $company;
    protected Transporter $transporter;
    protected Product $finishedProduct;
    protected Location $location;
    protected Order $order;
    protected OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatchUser = User::create([
            'name' => 'Dispatch Manager',
            'email' => 'dispatch@example.com',
            'password' => 'password123',
            'role' => 'DISPATCH',
            'status' => 'ACTIVE',
        ]);

        $this->company = Company::create([
            'name' => 'SIGMA INC',
            'contact' => '+91 9988776655',
            'address' => 'City Park',
        ]);

        $this->transporter = Transporter::create([
            'name' => 'GLOBAL LOGISTICS',
            'contact' => '+91 8877665544',
        ]);

        $this->finishedProduct = Product::create([
            'name' => 'Finished Pipe 75mm',
            'type' => 'FINISHED',
            'unit' => 'm',
            'is_active' => true,
        ]);

        $this->location = Location::create(['name' => 'Dispatch Bay']);

        // Add 500m finished stock
        Stock::create([
            'product_id' => $this->finishedProduct->id,
            'user_id' => $this->dispatchUser->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $this->location->id,
            'quantity' => 500,
            'transaction_type' => 'IN',
        ]);

        // Create an order for 300m
        $this->order = Order::create([
            'created_by' => $this->dispatchUser->id,
            'company_id' => $this->company->id,
            'transporter_id' => $this->transporter->id,
            'total' => 15000,
            'status' => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->finishedProduct->id,
            'grade' => 'NONE',
            'quantity' => 300,
            'price' => 50,
            'dispatched_qty' => 0,
        ]);
    }

    public function test_dispatch_fails_if_stock_insufficient(): void
    {
        $session = ['auth_user' => [
            'id' => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Attempting to dispatch 600m when stock is 500m
        $response = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity' => 600,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_dispatch_succeeds_deducts_stock_and_updates_order_status(): void
    {
        $session = ['auth_user' => [
            'id' => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $response = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity' => 200,
                ],
            ],
            'driver_no' => '9876543210',
            'lr_no' => 'LR-9999',
        ]);

        $response->assertJson(['success' => true]);

        // Order item dispatched_qty updated to 200
        $this->orderItem->refresh();
        $this->assertEquals(200, (float) $this->orderItem->dispatched_qty);

        // Order dispatch status updated to PARTIAL PENDING
        $this->order->refresh();
        $this->assertEquals('PARTIAL PENDING', $this->order->dispatch_status);

        // Stock OUT entry created
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->finishedProduct->id,
            'stage' => 'FINISHED',
            'quantity' => 200,
            'transaction_type' => 'OUT',
        ]);
    }

    public function test_dispatch_revert_restores_stock_and_order_status_atomically(): void
    {
        $session = ['auth_user' => [
            'id' => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Perform dispatch of 200m
        $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity' => 200,
                ],
            ],
        ]);

        $dispatchLog = DispatchLog::where('order_id', $this->order->id)->first();
        $this->assertNotNull($dispatchLog);

        // Revert dispatch
        $revertResponse = $this->withSession($session)->postJson("/dispatch/revert/{$dispatchLog->id}");
        $revertResponse->assertJson(['success' => true]);

        // Dispatch log deleted
        $this->assertDatabaseMissing('dispatch_logs', ['id' => $dispatchLog->id]);

        // Dispatched qty reset to 0
        $this->orderItem->refresh();
        $this->assertEquals(0, (float) $this->orderItem->dispatched_qty);

        // Order dispatch_status reset to PENDING
        $this->order->refresh();
        $this->assertEquals('PENDING', $this->order->dispatch_status);

        // Stock OUT entry removed, net stock back to 500m
        $netStock = $this->finishedProduct->currentStock('FINISHED');
        $this->assertEquals(500, $netStock);

        // Double revert attempt returns 404
        $secondRevert = $this->withSession($session)->postJson("/dispatch/revert/{$dispatchLog->id}");
        $secondRevert->assertStatus(404);
    }

    public function test_dispatch_home_displays_sales_by_sales_person(): void
    {
        $salesUser = User::create([
            'name'     => 'Rajesh Salesman',
            'email'    => 'rajesh@example.com',
            'password' => 'password123',
            'role'     => 'SALES',
            'status'   => 'ACTIVE',
        ]);

        $order = Order::create([
            'created_by'      => $salesUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 12000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $response = $this->withSession($session)->get('/dispatch/home');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('Sales By:', $content);
        $this->assertStringContainsString('Rajesh Salesman', $content);

        // Also test completed order
        $order->update(['dispatch_status' => 'COMPLETED']);
        $completedResponse = $this->withSession($session)->get('/dispatch/home?tab=completed');
        $completedResponse->assertStatus(200);
        $completedContent = $completedResponse->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $completedContent);
        $this->assertStringContainsString('Sales By:', $completedContent);
        $this->assertStringContainsString('Rajesh Salesman', $completedContent);
    }

    public function test_dispatch_history_displays_sales_by_and_searches_by_sales_person(): void
    {
        $salesUser = User::create([
            'name'     => 'Ankit Sales',
            'email'    => 'ankit@example.com',
            'password' => 'password123',
            'role'     => 'SALES',
            'status'   => 'ACTIVE',
        ]);

        $order = Order::create([
            'created_by'      => $salesUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 15000,
            'status'          => 'OPEN',
            'dispatch_status' => 'DONE',
        ]);

        $log = DispatchLog::create([
            'order_id'       => $order->id,
            'user_id'        => $this->dispatchUser->id,
            'transporter_id' => $this->transporter->id,
            'notes'          => 'Dispatched fully',
        ]);

        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $response = $this->withSession($session)->get('/dispatch/history');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('Sales By:', $content);
        $this->assertStringContainsString('Ankit Sales', $content);

        // Test search by salesperson name
        $searchResponse = $this->withSession($session)->get('/dispatch/history?q=Ankit');
        $searchResponse->assertStatus(200);
        $this->assertStringContainsString('Ankit Sales', $searchResponse->getContent());

        // Test search with unmatched query does not show the order
        $missResponse = $this->withSession($session)->get('/dispatch/history?q=NonExistentSalesPerson');
        $missResponse->assertStatus(200);
        $this->assertStringNotContainsString("Order #{$order->id}", $missResponse->getContent());
    }

    public function test_dispatch_report_displays_sales_by_and_searches_by_sales_person(): void
    {
        $salesUser = User::create([
            'name'     => 'Pooja Sales',
            'email'    => 'pooja@example.com',
            'password' => 'password123',
            'role'     => 'SALES',
            'status'   => 'ACTIVE',
        ]);

        $order = Order::create([
            'created_by'      => $salesUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 20000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $response = $this->withSession($session)->get('/dispatch/report');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("Order #{$order->id}", $content);
        $this->assertStringContainsString('Sales By:', $content);
        $this->assertStringContainsString('Pooja Sales', $content);

        // Test search by salesperson name
        $searchResponse = $this->withSession($session)->get('/dispatch/report?q=Pooja');
        $searchResponse->assertStatus(200);
        $this->assertStringContainsString('Pooja Sales', $searchResponse->getContent());

        // Test search with unmatched query does not show the order
        $missResponse = $this->withSession($session)->get('/dispatch/report?q=NonExistentSalesPerson');
        $missResponse->assertStatus(200);
        $this->assertStringNotContainsString("Order #{$order->id}", $missResponse->getContent());
    }

    public function test_order_items_product_name_type_and_aligned_order_badges(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // 1. Dispatch Home
        $homeResp = $this->withSession($session)->get('/dispatch/home');
        $homeResp->assertStatus(200);
        $homeContent = $homeResp->getContent();
        $this->assertStringContainsString('Finished Pipe 75mm', $homeContent);
        $this->assertStringContainsString('(FG)', $homeContent);
        $this->assertStringContainsString('ORDER:', $homeContent);
        $this->assertStringContainsString('DISPATCHED:', $homeContent);
        $this->assertStringContainsString('PENDING:', $homeContent);
        $this->assertStringContainsString('dispatch-item-badges', $homeContent);

        // 2. Dispatch Report
        $reportResp = $this->withSession($session)->get('/dispatch/report');
        $reportResp->assertStatus(200);
        $reportContent = $reportResp->getContent();
        $this->assertStringContainsString('Finished Pipe 75mm', $reportContent);
        $this->assertStringContainsString('(FG)', $reportContent);
        $this->assertStringContainsString('ORDER:', $reportContent);
        $this->assertStringContainsString('DISPATCHED:', $reportContent);
        $this->assertStringContainsString('PENDING:', $reportContent);
        $this->assertStringContainsString('dispatch-item-badges', $reportContent);
    }

    public function test_order_item_sync_dispatched_qty_corrects_drift(): void
    {
        // Simulate corrupted dispatched_qty (e.g. 250 instead of 0)
        $this->orderItem->update(['dispatched_qty' => 250]);
        $this->assertEquals(250, (float) $this->orderItem->dispatched_qty);
        $this->assertEquals(50, $this->orderItem->remainingQty());

        // Calling syncDispatchedQty should reset dispatched_qty to actual sum of logs (0)
        $actual = $this->orderItem->syncDispatchedQty();
        $this->assertEquals(0, $actual);
        $this->orderItem->refresh();
        $this->assertEquals(0, (float) $this->orderItem->dispatched_qty);
        $this->assertEquals(300, $this->orderItem->remainingQty());
    }

    public function test_dispatch_error_message_contains_grade_and_accurate_remaining_qty(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Attempting to dispatch 350 kg when order item quantity is 300 kg
        $response = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 350,
                    'location_splits' => [
                        ['location_key' => $this->location->name, 'dispatch_location_qty' => 350]
                    ]
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $content = $response->json();
        $this->assertStringContainsString('Cannot dispatch 350', $content['message']);
        $this->assertStringContainsString('Remaining pending order: 300', $content['message']);
        $this->assertStringContainsString('Total: 300', $content['message']);
        $this->assertEquals($this->orderItem->id, $content['item_id']);
        $this->assertEquals(300, $content['remaining_qty']);
    }

    public function test_dispatch_duplicate_submission_is_prevented(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $payload = [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 50,
                    'location_splits' => [
                        ['location_key' => $this->location->name, 'dispatch_location_qty' => 50]
                    ]
                ]
            ]
        ];

        // Acquire lock to simulate concurrent in-flight request
        $lock = \Illuminate\Support\Facades\Cache::lock("dispatch_lock_order_{$this->order->id}", 5);
        $lock->get();

        // Concurrent request gets 429
        $resp = $this->withSession($session)->postJson('/dispatch/action', $payload);
        $resp->assertStatus(429);
        $resp->assertJson(['success' => false]);
        $this->assertStringContainsString('currently in progress', $resp->json('message'));

        $lock->release();

        // Once lock is released, dispatch request proceeds
        $resp2 = $this->withSession($session)->postJson('/dispatch/action', $payload);
        $resp2->assertStatus(200);
        $resp2->assertJson(['success' => true]);
    }

    public function test_get_order_details_api_returns_synced_quantities(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $response = $this->withSession($session)->getJson("/api/dispatch/order-details/{$this->order->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $orderData = $response->json('order');
        $this->assertEquals($this->order->id, $orderData['id']);
        $this->assertCount(1, $orderData['items']);
        $this->assertEquals(300, $orderData['items'][0]['remainingQty']);
    }
}
