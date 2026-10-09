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

    public function test_dispatch_quantity_can_be_updated_and_stock_adjusted(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // 1. Dispatch 100 kg initially
        $postResp = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 100,
                    'location_splits' => [
                        ['location_key' => $this->location->name, 'dispatch_location_qty' => 100]
                    ]
                ]
            ]
        ]);
        $postResp->assertStatus(200);
        $postResp->assertJson(['success' => true]);

        $log = DispatchLog::where('order_id', $this->order->id)->latest()->first();
        $this->assertNotNull($log);
        $logItem = $log->dispatchItems->first();
        $this->assertEquals(100, (float) $logItem->quantity);

        $this->orderItem->refresh();
        $this->assertEquals(100, (float) $this->orderItem->dispatched_qty);
        $this->assertEquals(200, $this->orderItem->remainingQty());

        // 2. Update dispatched quantity from 100 kg to 150 kg
        $updateResp = $this->withSession($session)->postJson("/dispatch/update/{$log->id}", [
            'items' => [
                [
                    'dispatch_item_id' => $logItem->id,
                    'quantity'         => 150,
                    'location_splits'  => [
                        ['location_key' => $this->location->name, 'dispatch_location_qty' => 150]
                    ]
                ]
            ],
            'notes' => 'Updated quantity to 150'
        ]);
        $updateResp->assertStatus(200);
        $updateResp->assertJson(['success' => true]);

        // 3. Verify dispatch log item, order item, and remaining quantities
        $logItem->refresh();
        $this->assertEquals(150, (float) $logItem->quantity);

        $this->orderItem->refresh();
        $this->assertEquals(150, (float) $this->orderItem->dispatched_qty);
        $this->assertEquals(150, $this->orderItem->remainingQty());

        $this->order->refresh();
        $this->assertEquals('PARTIAL PENDING', $this->order->dispatch_status);
        $this->assertEquals('OPEN', $this->order->status);

        // 4. Verify total stock deductions net 150 kg
        $totalOut = Stock::where('product_id', $this->finishedProduct->id)
            ->where('transaction_type', 'OUT')
            ->sum('quantity');
        $this->assertEquals(150, (float) $totalOut);
    }

    public function test_dispatch_quantity_update_exceeding_order_limit_fails(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Dispatch 100 kg initially
        $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 100,
                    'location_splits' => [
                        ['location_key' => $this->location->name, 'dispatch_location_qty' => 100]
                    ]
                ]
            ]
        ]);

        $log = DispatchLog::where('order_id', $this->order->id)->latest()->first();
        $logItem = $log->dispatchItems->first();

        // Attempting to update to 350 kg (order total is only 300 kg)
        $resp = $this->withSession($session)->postJson("/dispatch/update/{$log->id}", [
            'items' => [
                [
                    'dispatch_item_id' => $logItem->id,
                    'quantity'         => 350,
                ]
            ]
        ]);
        $resp->assertStatus(422);
        $resp->assertJson(['success' => false]);
        $this->assertStringContainsString('Maximum allowed', $resp->json('message'));
    }

    public function test_dispatch_quantity_update_to_full_order_marks_order_as_done(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Dispatch 100 kg initially
        $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 100,
                ]
            ]
        ]);

        $log = DispatchLog::where('order_id', $this->order->id)->latest()->first();
        $logItem = $log->dispatchItems->first();

        // Update to 300 kg (full order total)
        $resp = $this->withSession($session)->postJson("/dispatch/update/{$log->id}", [
            'items' => [
                [
                    'dispatch_item_id' => $logItem->id,
                    'quantity'         => 300,
                ]
            ]
        ]);
        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals('DONE', $this->order->dispatch_status);
        $this->assertEquals('CLOSED', $this->order->status);

        // Now reduce back to 200 kg and verify order reopens
        $resp2 = $this->withSession($session)->postJson("/dispatch/update/{$log->id}", [
            'items' => [
                [
                    'dispatch_item_id' => $logItem->id,
                    'quantity'         => 200,
                ]
            ]
        ]);
        $resp2->assertStatus(200);
        $resp2->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals('PARTIAL PENDING', $this->order->dispatch_status);
        $this->assertEquals('OPEN', $this->order->status);
    }

    public function test_dispatch_history_does_not_render_edit_qty_buttons(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $this->order->id,
            'items'    => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity'      => 100,
                ]
            ]
        ]);

        $resp = $this->withSession($session)->get('/dispatch/history');
        $resp->assertStatus(200);
        $content = $resp->getContent();
        $this->assertStringNotContainsString('Edit Qty', $content);
        $this->assertStringNotContainsString('openEditDispatchModal', $content);
        $this->assertStringContainsString('Revert Dispatch', $content);
    }

    public function test_pending_pdf_download(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Create an order with null created_at to verify null-safety
        $nullDateOrder = Order::create([
            'created_by'      => $this->dispatchUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $nullDateOrder->id)->update(['created_at' => null]);

        OrderItem::create([
            'order_id'   => $nullDateOrder->id,
            'product_id' => $this->finishedProduct->id,
            'grade'      => 'A',
            'quantity'   => 50,
            'price'      => 100,
        ]);

        $resp = $this->withSession($session)->get('/dispatch/history/dispatch/pdf?range=all&status=PENDING');
        $this->assertEquals(200, $resp->status());
        $resp->assertHeader('Content-Type', 'application/pdf');

        // Also test direct fallback routes
        $resp2 = $this->withSession($session)->get('/dispatch/history/pdf?range=all&status=PENDING');
        $this->assertEquals(200, $resp2->status());
        $resp2->assertHeader('Content-Type', 'application/pdf');

        $resp3 = $this->withSession($session)->get('/dispatch/report/pdf?range=all&status=PENDING');
        $this->assertEquals(200, $resp3->status());
        $resp3->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_dispatch_history_filters_status_properly(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin User',
            'email'       => 'subadmin@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['dispatch_history', 'dispatch_action'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $dispatchSession = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // 1. $this->order is PENDING (300m, 0 dispatched)

        // 2. Create partial order
        $partialOrder = Order::create([
            'created_by'      => $this->dispatchUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 10000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        $partialItem = OrderItem::create([
            'order_id'       => $partialOrder->id,
            'product_id'     => $this->finishedProduct->id,
            'grade'          => 'NONE',
            'quantity'       => 200,
            'price'          => 50,
            'dispatched_qty' => 0,
        ]);
        // Dispatch partial: 50m
        $resp1 = $this->withSession($dispatchSession)->postJson('/dispatch/action', [
            'order_id' => $partialOrder->id,
            'items'    => [
                [
                    'order_item_id' => $partialItem->id,
                    'quantity'      => 50,
                ]
            ]
        ]);
        $resp1->assertJson(['success' => true]);

        // 3. Create done order
        $doneOrder = Order::create([
            'created_by'      => $this->dispatchUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 5000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        $doneItem = OrderItem::create([
            'order_id'       => $doneOrder->id,
            'product_id'     => $this->finishedProduct->id,
            'grade'          => 'NONE',
            'quantity'       => 50,
            'price'          => 100,
            'dispatched_qty' => 0,
        ]);
        // Dispatch all: 50m
        $resp2 = $this->withSession($dispatchSession)->postJson('/dispatch/action', [
            'order_id' => $doneOrder->id,
            'items'    => [
                [
                    'order_item_id' => $doneItem->id,
                    'quantity'      => 50,
                ]
            ]
        ]);
        $resp2->assertJson(['success' => true]);

        // Undispatched orders (like $this->order) do NOT appear in Dispatch History
        // Only orders with actual dispatches (partial or fully dispatched) appear
        $allHistoryResp = $this->withSession($session)->get('/sub_admin/dispatch/history');
        $allHistoryResp->assertStatus(200);
        $allContent = $allHistoryResp->getContent();
        $this->assertStringNotContainsString("Order #{$this->order->id}", $allContent);
        $this->assertStringContainsString("Order #{$partialOrder->id}", $allContent);
        $this->assertStringContainsString("Order #{$doneOrder->id}", $allContent);

        // Test PARTIAL filter
        $partialResp = $this->withSession($session)->get('/sub_admin/dispatch/history?range=all&company_id=&status=PARTIAL&q=');
        $partialResp->assertStatus(200);
        $partialContent = $partialResp->getContent();
        $this->assertStringContainsString("Order #{$partialOrder->id}", $partialContent);
        $this->assertStringNotContainsString("Order #{$this->order->id}", $partialContent);
        $this->assertStringNotContainsString("Order #{$doneOrder->id}", $partialContent);

        // Test FULLY DISPATCHED (DONE) filter
        $doneResp = $this->withSession($session)->get('/sub_admin/dispatch/history?range=all&company_id=&status=DONE&q=');
        $doneResp->assertStatus(200);
        $doneContent = $doneResp->getContent();
        $this->assertStringContainsString("Order #{$doneOrder->id}", $doneContent);
        $this->assertStringNotContainsString("Order #{$this->order->id}", $doneContent);
        $this->assertStringNotContainsString("Order #{$partialOrder->id}", $doneContent);

        // Test downloading PDF for both partial and done dispatches
        $partialLog = DispatchLog::where('order_id', $partialOrder->id)->first();
        $doneLog = DispatchLog::where('order_id', $doneOrder->id)->first();
        $this->assertNotNull($partialLog);
        $this->assertNotNull($doneLog);

        $pdfPartialResp = $this->withSession($session)->get('/sub_admin/pdf/' . $partialLog->id);
        $pdfPartialResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfPartialResp->headers->get('content-type'));

        $pdfDoneResp = $this->withSession($session)->get('/sub_admin/pdf/' . $doneLog->id);
        $pdfDoneResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfDoneResp->headers->get('content-type'));

        $pdfDispatchResp = $this->withSession($dispatchSession)->get('/dispatch/pdf/' . $doneLog->id);
        $pdfDispatchResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfDispatchResp->headers->get('content-type'));
    }

    public function test_each_order_displays_its_own_particular_uploaded_lr_image_and_can_update_lr_via_sub_admin()
    {
        $subAdmin = User::create([
            'name'     => 'Sub Admin User',
            'email'    => 'subadmin_lr@test.com',
            'password' => bcrypt('password'),
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['dispatch_history', 'dispatch_action', 'edit_dispatch_action', 'edit_dispatch_history'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $company = Company::create(['name' => 'Alpha Industrial', 'contact' => '9876543210', 'address' => 'Surat', 'gst' => '24ABCDE1234F1Z5']);
        $product = Product::create(['name' => 'PVC Pipe 50mm', 'type' => 'FINISHED', 'unit' => 'KG']);

        // Order 1
        $order1 = Order::create([
            'company_id'      => $company->id,
            'created_by'      => $subAdmin->id,
            'total'           => 12000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        $item1 = OrderItem::create([
            'order_id'       => $order1->id,
            'product_id'     => $product->id,
            'quantity'       => 100,
            'dispatched_qty' => 0,
            'price'          => 120,
        ]);

        // Order 2
        $order2 = Order::create([
            'company_id'      => $company->id,
            'created_by'      => $subAdmin->id,
            'total'           => 8000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        $item2 = OrderItem::create([
            'order_id'       => $order2->id,
            'product_id'     => $product->id,
            'quantity'       => 50,
            'dispatched_qty' => 0,
            'price'          => 160,
        ]);

        // Dispatch Order 1 with LR Image A
        $fakeImgA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $log1 = DispatchLog::create([
            'order_id'       => $order1->id,
            'user_id'        => $subAdmin->id,
            'lr_image_path'  => 'lr_images/test_order1_lr.jpg',
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log1->id,
            'order_item_id'   => $item1->id,
            'quantity'        => 100,
        ]);

        // Dispatch Order 2 with LR Image B
        $log2 = DispatchLog::create([
            'order_id'       => $order2->id,
            'user_id'        => $subAdmin->id,
            'lr_image_path'  => 'lr_images/test_order2_lr.jpg',
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log2->id,
            'order_item_id'   => $item2->id,
            'quantity'        => 50,
        ]);

        // Test GET /sub_admin/dispatch/history displays the particular LR image for each order
        $historyResp = $this->withSession($session)->get('/sub_admin/dispatch/history');
        $historyResp->assertStatus(200);
        $content = $historyResp->getContent();

        $this->assertStringContainsString('test_order1_lr.jpg', $content);
        $this->assertStringContainsString('test_order2_lr.jpg', $content);

        // Test updating LR via /sub_admin/dispatch/update-lr
        $updateResp1 = $this->withSession($session)->postJson('/sub_admin/dispatch/update-lr', [
            'log_id'   => $log1->id,
            'lr_image' => $fakeImgA,
        ]);
        $updateResp1->assertStatus(200);
        $updateResp1->assertJson(['success' => true]);

        // Test updating LR via /sub_admin/update-lr
        $updateResp2 = $this->withSession($session)->postJson('/sub_admin/update-lr', [
            'log_id'   => $log2->id,
            'lr_image' => $fakeImgA,
        ]);
        $updateResp2->assertStatus(200);
        // Test downloading PDF via /sub_admin/pdf/{id} and /sub_admin/dispatch/pdf/{id}
        $pdfResp1 = $this->withSession($session)->get('/sub_admin/pdf/' . $log1->id);
        $pdfResp1->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp1->headers->get('content-type'));

        $pdfResp2 = $this->withSession($session)->get('/sub_admin/dispatch/pdf/' . $log1->id);
        $pdfResp2->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp2->headers->get('content-type'));

        // Clean up any test files
        $log1->refresh();
        $log2->refresh();
        if ($log1->lr_image_path && file_exists(public_path($log1->lr_image_path))) {
            @unlink(public_path($log1->lr_image_path));
        }
        if ($log2->lr_image_path && file_exists(public_path($log2->lr_image_path))) {
            @unlink(public_path($log2->lr_image_path));
        }
    }

    public function test_dispatch_history_displays_dispatch_id_date_and_previous_dispatch_quantity(): void
    {
        $session = ['auth_user' => [
            'id'   => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        // Round 1 dispatch: 200 kg of 500 kg
        $log1 = \App\Models\DispatchLog::create([
            'user_id'        => $this->dispatchUser->id,
            'order_id'       => $this->order->id,
            'transporter_id' => $this->transporter->id,
            'created_at'     => now()->subHour(),
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log1->id,
            'order_item_id'   => $this->orderItem->id,
            'quantity'        => 200,
        ]);
        $this->orderItem->update(['dispatched_qty' => 200]);

        $resp1 = $this->withSession($session)->get('/dispatch/history');
        $resp1->assertStatus(200);
        $content1 = $resp1->getContent();

        $this->assertStringContainsString("DSP #{$log1->id}", $content1);
        $this->assertStringContainsString("Dispatch ID:", $content1);
        $this->assertStringContainsString("Dispatch Date:", $content1);
        $this->assertStringContainsString("PREV. DISPATCH:", $content1);
        $this->assertStringContainsString("0 kg", $content1);

        // Round 2 dispatch: 150 kg
        $log2 = \App\Models\DispatchLog::create([
            'user_id'        => $this->dispatchUser->id,
            'order_id'       => $this->order->id,
            'transporter_id' => $this->transporter->id,
            'created_at'     => now(),
        ]);
        \App\Models\DispatchLogItem::create([
            'dispatch_log_id' => $log2->id,
            'order_item_id'   => $this->orderItem->id,
            'quantity'        => 150,
        ]);
        $this->orderItem->update(['dispatched_qty' => 350]);

        $resp2 = $this->withSession($session)->get('/dispatch/history');
        $resp2->assertStatus(200);
        $content2 = $resp2->getContent();

        // Round 2 should have DSP #log2->id and show 200 kg as previous dispatch
        $this->assertStringContainsString("DSP #{$log2->id}", $content2);
        $this->assertStringContainsString("Prev. Dispatched:", $content2);
        $this->assertStringContainsString("200 kg", $content2);
    }
}
