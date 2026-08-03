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

        // Order dispatch status updated to PARTIAL
        $this->order->refresh();
        $this->assertEquals('PARTIAL', $this->order->dispatch_status);

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
}
