<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderEditPartialAndDispatchedTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesUser;
    protected Company $company;
    protected Transporter $transporter;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesUser = User::create([
            'name'     => 'Sales User 3',
            'email'    => 'sales3@example.com',
            'password' => 'secret123',
            'role'     => 'SALES',
            'status'   => 'ACTIVE',
            'branch'   => 'MAIN',
        ]);

        $this->company = Company::create([
            'name'    => 'BETA ENTERPRISES',
            'contact' => '+91 9998887776',
            'address' => 'Plot 42, GIDC',
        ]);

        $this->transporter = Transporter::create([
            'name' => 'SUPER FAST LOGISTICS',
        ]);

        $this->productA = Product::create([
            'name'   => 'PVC PIPE 20MM',
            'unit'   => 'KG',
            'type'   => 'FINISHED',
            'status' => 'ACTIVE',
        ]);

        $this->productB = Product::create([
            'name'   => 'PVC ELBOW 20MM',
            'unit'   => 'KG',
            'type'   => 'FINISHED',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_partial_order_allows_quantity_edit_but_prevents_product_removal_and_fully_dispatched_qty_edit(): void
    {
        // Create an order with 2 items:
        // Item 1: 100 kg ordered, 40 kg dispatched (PARTIAL)
        // Item 2: 50 kg ordered, 50 kg dispatched (FULLY DISPATCHED)
        $order = Order::create([
            'created_by'      => $this->salesUser->id,
            'company_id'      => $this->company->id,
            'transporter_id'  => $this->transporter->id,
            'total'           => 15000.00,
            'date'            => now(),
            'due_date'        => now()->addDays(5),
            'status'          => 'PENDING',
            'dispatch_status' => 'PARTIAL',
            'notes'           => 'Initial order instructions',
        ]);

        $item1 = OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productA->id,
            'grade'          => 'NA',
            'quantity'       => 100.00,
            'dispatched_qty' => 40.00,
            'price'          => 100.00,
        ]);

        $item2 = OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $this->productB->id,
            'grade'          => 'NA',
            'quantity'       => 50.00,
            'dispatched_qty' => 50.00,
            'price'          => 100.00,
        ]);

        $session = [
            'auth_user' => [
                'id'     => $this->salesUser->id,
                'name'   => $this->salesUser->name,
                'role'   => 'SALES',
                'branch' => 'MAIN',
            ],
        ];

        // 1. Visit edit view (/sales3/action?edit=...)
        $viewResponse = $this->withSession($session)->get('/sales/action?edit=' . $order->id);
        $viewResponse->assertOk();
        $viewResponse->assertSee('Order #' . $order->id . ' is Partially Dispatched', false);

        // 2. Attempt to remove item 1 from a partially dispatched order -> MUST FAIL
        $removePayload = [
            'order_id'       => $order->id,
            'company_id'     => $this->company->id,
            'transporter_id' => $this->transporter->id,
            'due_date'       => now()->addDays(5)->format('Y-m-d'),
            'notes'          => 'Trying to remove item 1',
            'items'          => [
                [
                    'id'         => $item2->id,
                    'product_id' => $this->productB->id,
                    'grade'      => 'NA',
                    'quantity'   => 50.00,
                    'price'      => 100.00,
                ],
            ],
        ];

        $failResponse1 = $this->withSession($session)->postJson('/sales/action', $removePayload);
        $failResponse1->assertStatus(422);
        $failResponse1->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('Cannot remove', $failResponse1->json('message'));

        // 3. Attempt to modify quantity of item 2 (which is already FULLY DISPATCHED: 50kg) -> MUST FAIL
        $editFullyDispatchedPayload = [
            'order_id'       => $order->id,
            'company_id'     => $this->company->id,
            'transporter_id' => $this->transporter->id,
            'due_date'       => now()->addDays(5)->format('Y-m-d'),
            'notes'          => 'Trying to increase fully dispatched item quantity',
            'items'          => [
                [
                    'id'         => $item1->id,
                    'product_id' => $this->productA->id,
                    'grade'      => 'NA',
                    'quantity'   => 120.00, // Valid increase for partial item
                    'price'      => 100.00,
                ],
                [
                    'id'         => $item2->id,
                    'product_id' => $this->productB->id,
                    'grade'      => 'NA',
                    'quantity'   => 70.00,  // INVALID: item 2 is fully dispatched (50/50kg)
                    'price'      => 100.00,
                ],
            ],
        ];

        $failResponse2 = $this->withSession($session)->postJson('/sales/action', $editFullyDispatchedPayload);
        $failResponse2->assertStatus(422);
        $failResponse2->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('already fully dispatched', $failResponse2->json('message'));

        // 4. Attempt to reduce partial item quantity below dispatched amount (e.g. 30kg < 40kg) -> MUST FAIL
        $reduceBelowDispatchedPayload = [
            'order_id'       => $order->id,
            'company_id'     => $this->company->id,
            'transporter_id' => $this->transporter->id,
            'due_date'       => now()->addDays(5)->format('Y-m-d'),
            'notes'          => 'Trying to reduce below dispatched',
            'items'          => [
                [
                    'id'         => $item1->id,
                    'product_id' => $this->productA->id,
                    'grade'      => 'NA',
                    'quantity'   => 30.00, // INVALID: 40kg already dispatched
                    'price'      => 100.00,
                ],
                [
                    'id'         => $item2->id,
                    'product_id' => $this->productB->id,
                    'grade'      => 'NA',
                    'quantity'   => 50.00, // Kept intact
                    'price'      => 100.00,
                ],
            ],
        ];

        $failResponse3 = $this->withSession($session)->postJson('/sales/action', $reduceBelowDispatchedPayload);
        $failResponse3->assertStatus(422);
        $failResponse3->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('Minimum allowed: 40', $failResponse3->json('message'));

        // 5. Valid update: Keep fully dispatched item at 50kg, and edit partial item quantity to 120kg -> MUST SUCCEED
        $validUpdatePayload = [
            'order_id'       => $order->id,
            'company_id'     => $this->company->id,
            'transporter_id' => $this->transporter->id,
            'due_date'       => now()->addDays(5)->format('Y-m-d'),
            'notes'          => 'Updated valid quantity for partial item',
            'items'          => [
                [
                    'id'         => $item1->id,
                    'product_id' => $this->productA->id,
                    'grade'      => 'NA',
                    'quantity'   => 120.00, // Valid
                    'price'      => 100.00,
                ],
                [
                    'id'         => $item2->id,
                    'product_id' => $this->productB->id,
                    'grade'      => 'NA',
                    'quantity'   => 50.00, // Kept intact
                    'price'      => 100.00,
                ],
            ],
        ];

        $successResponse = $this->withSession($session)->postJson('/sales/action', $validUpdatePayload);
        $successResponse->assertOk();
        $successResponse->assertJson(['success' => true]);

        $this->assertEquals(120.00, (float) $item1->fresh()->quantity);
        $this->assertEquals(50.00, (float) $item2->fresh()->quantity);
    }
}
