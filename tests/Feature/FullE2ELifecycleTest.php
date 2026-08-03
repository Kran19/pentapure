<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Grade;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullE2ELifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_supply_chain_and_dispatch_revert_lifecycle(): void
    {
        // 1. Setup Users
        $rawUser = User::create(['name' => 'Raw Officer', 'email' => 'r@ex.com', 'password' => 'pass', 'role' => 'RAW', 'status' => 'ACTIVE']);
        $semiUser = User::create(['name' => 'Semi Officer', 'email' => 'sm@ex.com', 'password' => 'pass', 'role' => 'SEMI', 'status' => 'ACTIVE']);
        $finishedUser = User::create(['name' => 'Finished Officer', 'email' => 'f@ex.com', 'password' => 'pass', 'role' => 'FINISHED', 'status' => 'ACTIVE']);
        $salesUser = User::create(['name' => 'Sales Officer', 'email' => 's@ex.com', 'password' => 'pass', 'role' => 'SALES', 'status' => 'ACTIVE']);
        $dispatchUser = User::create(['name' => 'Dispatch Officer', 'email' => 'd@ex.com', 'password' => 'pass', 'role' => 'DISPATCH', 'status' => 'ACTIVE']);

        // 2. Setup Products & Location
        $location = Location::create(['name' => 'Central Warehouse']);
        $gradeA = Grade::create(['name' => 'GRADE-A', 'is_active' => true]);

        $rawProd = Product::create(['name' => 'Virgin Resin', 'type' => 'RAW', 'unit' => 'kg', 'is_active' => true]);
        $rawProd->grades()->attach($gradeA->id);

        $semiProd = Product::create(['name' => 'Compounded Granules', 'type' => 'SEMI', 'unit' => 'kg', 'is_active' => true]);
        $semiProd->grades()->attach($gradeA->id);

        $finishedProd = Product::create(['name' => 'PentaPipe 110mm', 'type' => 'FINISHED', 'unit' => 'm', 'is_active' => true]);
        $finishedProd->grades()->attach($gradeA->id);

        // --- STEP 1: RAW INWARD (1000kg) ---
        $resInward = $this->withSession(['auth_user' => ['id' => $rawUser->id, 'name' => $rawUser->name, 'role' => 'RAW']])
            ->postJson('/raw/action', [
                'product_id' => $rawProd->id,
                'quantity' => 1000,
                'grade' => 'GRADE-A',
                'location' => 'Central Warehouse',
            ]);
        $resInward->assertJson(['success' => true]);
        $this->assertEquals(1000, $rawProd->currentStock('RAW', 'GRADE-A'));

        // --- STEP 2: RAW -> SEMI TRANSFER (500kg) ---
        $resTransfer = $this->withSession(['auth_user' => ['id' => $rawUser->id, 'name' => $rawUser->name, 'role' => 'RAW']])
            ->postJson('/raw/transfer-to-semi', [
                'product_id' => $rawProd->id,
                'quantity' => 500,
                'grade' => 'GRADE-A',
                'location' => 'Central Warehouse',
            ]);
        $resTransfer->assertJson(['success' => true]);
        $this->assertEquals(500, $rawProd->currentStock('RAW', 'GRADE-A'));
        $this->assertEquals(500, $rawProd->currentStock('SEMI', 'GRADE-A'));

        // --- STEP 3: FINISHED PRODUCTION (Consume 300kg SEMI -> Produce 200m FINISHED) ---
        $resProd = $this->withSession(['auth_user' => ['id' => $finishedUser->id, 'name' => $finishedUser->name, 'role' => 'FINISHED']])
            ->postJson('/finished/action', [
                'output_product_id' => $finishedProd->id,
                'output_grade' => 'GRADE-A',
                'output_qty' => 200,
                'location' => 'Central Warehouse',
                'inputs' => [
                    [
                        'product_id' => $rawProd->id,
                        'grade' => 'GRADE-A',
                        'stage' => 'SEMI',
                        'quantity' => 300,
                    ]
                ]
            ]);
        $resProd->assertJson(['success' => true]);
        $this->assertEquals(200, $rawProd->currentStock('SEMI', 'GRADE-A')); // 500 - 300 = 200
        $this->assertEquals(200, $finishedProd->currentStock('FINISHED', 'GRADE-A'));

        // --- STEP 4: SALES ORDER (200m FINISHED) ---
        $company = Company::create(['name' => 'INFRA CORP', 'contact' => '+91 9999900000', 'address' => 'City Site']);
        $transporter = Transporter::create(['name' => 'SUPER LOGISTICS', 'contact' => '+91 8888800000']);

        $resOrder = $this->withSession(['auth_user' => ['id' => $salesUser->id, 'name' => $salesUser->name, 'role' => 'SALES']])
            ->postJson('/sales/order', [
                'company_id' => $company->id,
                'transporter_id' => $transporter->id,
                'items' => [
                    [
                        'product_id' => $finishedProd->id,
                        'grade' => 'GRADE-A',
                        'quantity' => 200,
                        'price' => 100,
                    ]
                ]
            ]);
        $resOrder->assertJson(['success' => true]);

        $order = Order::with('items')->latest()->first();
        $orderItem = $order->items->first();

        // --- STEP 5: DISPATCH (150m) ---
        $resDispatch = $this->withSession(['auth_user' => ['id' => $dispatchUser->id, 'name' => $dispatchUser->name, 'role' => 'DISPATCH']])
            ->postJson('/dispatch/action', [
                'order_id' => $order->id,
                'items' => [
                    [
                        'order_item_id' => $orderItem->id,
                        'quantity' => 150,
                    ]
                ]
            ]);
        $resDispatch->assertJson(['success' => true]);
        $this->assertEquals(50, $finishedProd->currentStock('FINISHED', 'GRADE-A')); // 200 - 150 = 50

        // --- STEP 6: REVERT DISPATCH ---
        $dispatchLog = DispatchLog::where('order_id', $order->id)->first();
        $resRevert = $this->withSession(['auth_user' => ['id' => $dispatchUser->id, 'name' => $dispatchUser->name, 'role' => 'DISPATCH']])
            ->postJson("/dispatch/revert/{$dispatchLog->id}");
        $resRevert->assertJson(['success' => true]);
        $this->assertEquals(200, $finishedProd->currentStock('FINISHED', 'GRADE-A')); // Restored to 200

        // --- STEP 7: REDISPATCH FULL QUANTITY (200m) ---
        $resRedispatch = $this->withSession(['auth_user' => ['id' => $dispatchUser->id, 'name' => $dispatchUser->name, 'role' => 'DISPATCH']])
            ->postJson('/dispatch/action', [
                'order_id' => $order->id,
                'items' => [
                    [
                        'order_item_id' => $orderItem->id,
                        'quantity' => 200,
                    ]
                ]
            ]);
        $resRedispatch->assertJson(['success' => true]);

        // Final Stock Reconciliation Check
        $this->assertEquals(0, $finishedProd->currentStock('FINISHED', 'GRADE-A'));
        $order->refresh();
        $this->assertEquals('DONE', $order->dispatch_status);
        $this->assertEquals('CLOSED', $order->status);
    }

    public function test_full_cashier_financial_reconciliation_lifecycle(): void
    {
        $cashier = User::create(['name' => 'Cashier Main', 'email' => 'cm@ex.com', 'password' => 'pass', 'role' => 'CASHIER', 'status' => 'ACTIVE']);
        $session = ['auth_user' => ['id' => $cashier->id, 'name' => $cashier->name, 'role' => 'CASHIER']];

        // Cash IN: $1000
        $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [['type' => 'IN', 'amount' => 1000.00, 'category' => 'sales']]
        ]);

        // Cash OUT: $300
        $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [['type' => 'OUT', 'amount' => 300.00, 'category' => 'office_supplies']]
        ]);

        $outTx = Transaction::where('user_id', $cashier->id)->where('type', 'OUT')->first();

        // Edit Cash OUT from $300 to $250
        $this->withSession($session)->putJson("/cashier/action/{$outTx->id}", [
            'amount' => 250.00,
            'category' => 'office_supplies',
        ]);

        // Verify Ledger Totals
        $resLedger = $this->withSession($session)->get('/cashier/ledger');
        $resLedger->assertStatus(200);

        $summary = $resLedger->viewData('pageData')['summary'];
        $this->assertEquals(1000.00, (float) $summary['totalIn']);
        $this->assertEquals(250.00, (float) $summary['totalOut']);
        $this->assertEquals(750.00, (float) $summary['balance']);
    }
}
