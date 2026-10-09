<?php

namespace Tests\Feature;

    use App\Models\Company;
    use App\Models\DispatchLog;
    use App\Models\Grade;
    use App\Models\Order;
    use App\Models\OrderItem;
    use App\Models\Product;
    use App\Models\Transporter;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class SalesTest extends TestCase
    {
        use RefreshDatabase;

        protected User $salesUser;
        protected Company $company;
        protected Transporter $transporter;
        protected Product $product;
        protected Grade $gradeA;
        protected Grade $gradeB;

        protected function setUp(): void
        {
            parent::setUp();

            $this->salesUser = User::create([
                'name' => 'Sales Manager',
                'email' => 'sales@example.com',
                'password' => 'password123',
                'role' => 'SALES',
                'status' => 'ACTIVE',
            ]);

            $this->company = Company::create([
                'name' => 'ACME CORP',
                'gst' => '00AAAAA0000A1Z0',
                'contact' => '+91 9876543210',
                'address' => '123 Main St',
            ]);

            $this->transporter = Transporter::create([
                'name' => 'EXPRESS LOGISTICS',
                'gst' => 'N/A',
                'contact' => '+91 9123456789',
                'vehicles' => 'Truck MH-12-1234',
            ]);

            $this->product = Product::create([
                'name' => 'Finished Pipe 50mm',
                'type' => 'FINISHED',
                'unit' => 'm',
                'is_active' => true,
            ]);

            $this->gradeA = Grade::create(['name' => 'GRADE-A', 'is_active' => true]);
            $this->gradeB = Grade::create(['name' => 'GRADE-B', 'is_active' => true]);

            // Attach GRADE-A to $product
            $this->product->grades()->attach($this->gradeA->id);
        }

        public function test_sales_user_can_create_company_and_transporter(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $compResponse = $this->withSession($session)->postJson('/sales/company', [
                'name' => 'BETA LTD',
                'gst' => 'N/A',
                'contact' => '9998887776',
                'address' => 'Industrial Area',
                'pincode' => '380001',
            ]);

            $compResponse->assertJson(['success' => true]);
            $this->assertDatabaseHas('companies', ['name' => 'BETA LTD', 'pincode' => '380001']);

            $transResponse = $this->withSession($session)->postJson('/sales/transport', [
                'name' => 'FAST FREIGHT',
                'gst' => 'N/A',
                'contact' => '8887776665',
            ]);

            $transResponse->assertJson(['success' => true]);
            $this->assertDatabaseHas('transporters', ['name' => 'FAST FREIGHT']);

            // Test creating company with minimal fields (address and contact omitted)
            $minimalComp = $this->withSession($session)->postJson('/sales/company', [
                'name' => 'MINIMAL CO',
                'gst' => '22AAAAA0000A1Z2',
            ]);
            $minimalComp->assertJson(['success' => true]);
            $this->assertDatabaseHas('companies', ['name' => 'MINIMAL CO', 'gst' => '22AAAAA0000A1Z2']);

            // Test creating transporter with minimal fields (contact and vehicles omitted)
            $minimalTrans = $this->withSession($session)->postJson('/sales/transport', [
                'name' => 'MINIMAL TRANS',
                'gst' => '33BBBBB0000B1Z3',
            ]);
            $minimalTrans->assertJson(['success' => true]);
            $this->assertDatabaseHas('transporters', ['name' => 'MINIMAL TRANS', 'gst' => '33BBBBB0000B1Z3']);
        }

        public function test_sales_user_can_create_order_with_valid_product_and_grade(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $response = $this->withSession($session)->postJson('/sales/order', [
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'due_date' => '2026-10-20',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'grade' => 'GRADE-A',
                        'quantity' => 100,
                        'price' => 50.50,
                    ]
                ],
                'notes' => 'Test order creation',
            ]);

            $response->assertJson(['success' => true]);

            $this->assertDatabaseHas('orders', [
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'status' => 'OPEN',
                'dispatch_status' => 'PENDING',
                'total' => 5050.00,
            ]);

            $createdOrder = Order::latest('id')->first();
            $this->assertEquals('2026-10-20', \Carbon\Carbon::parse($createdOrder->due_date)->format('Y-m-d'));
        }

        public function test_sales_order_creation_fails_when_grade_invalid_for_product(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            // GRADE-B is not attached to $product
            $response = $this->withSession($session)->postJson('/sales/order', [
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'grade' => 'GRADE-B',
                        'quantity' => 100,
                        'price' => 50.50,
                    ]
                ],
            ]);

            $response->assertStatus(422);
            $response->assertJson(['success' => false]);
        }

        public function test_sales_user_can_cancel_open_order(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $order = Order::create([
                'created_by' => $this->salesUser->id,
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'total' => 1000,
                'status' => 'OPEN',
                'dispatch_status' => 'PENDING',
            ]);

            $response = $this->withSession($session)->postJson("/sales/order/{$order->id}/cancel");
            $response->assertJson(['success' => true]);

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => 'CANCELLED',
            ]);
        }

        public function test_cannot_cancel_dispatched_order(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $order = Order::create([
                'created_by' => $this->salesUser->id,
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'total' => 1000,
                'status' => 'OPEN',
                'dispatch_status' => 'PARTIAL',
            ]);

            $response = $this->withSession($session)->postJson("/sales/order/{$order->id}/cancel");
            $response->assertStatus(422);
        }

        public function test_sales_user_can_delete_unused_company_and_transporter(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $tempComp = Company::create(['name' => 'TEMP COMP']);
            $tempTrans = Transporter::create(['name' => 'TEMP TRANS']);

            $delComp = $this->withSession($session)->deleteJson("/sales/company/{$tempComp->id}");
            $delComp->assertJson(['success' => true]);
            $this->assertDatabaseMissing('companies', ['id' => $tempComp->id]);

            $delTrans = $this->withSession($session)->deleteJson("/sales/transport/{$tempTrans->id}");
            $delTrans->assertJson(['success' => true]);
            $this->assertDatabaseMissing('transporters', ['id' => $tempTrans->id]);
        }

        public function test_sales_order_pdf_download_routes(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $order = Order::create([
                'created_by' => $this->salesUser->id,
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'total' => 1000,
                'status' => 'OPEN',
                'dispatch_status' => 'PENDING',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $this->product->id,
                'grade' => 'GRADE-A',
                'quantity' => 10,
                'price' => 100,
            ]);

            $response1 = $this->withSession($session)->get("/sales/order/pdf/{$order->id}");
            $response1->assertStatus(200);

            $response2 = $this->withSession($session)->get("/sales/sales/order/pdf/{$order->id}");
            $response2->assertStatus(200);
        }

        public function test_na_grade_is_valid_for_product_with_assigned_grades(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            // Attach specific grades to product
            $this->product->grades()->sync([$this->gradeA->id, $this->gradeB->id]);

            $response = $this->withSession($session)->postJson('/sales/action', [
                'company_id' => $this->company->id,
                'transporter_id' => $this->transporter->id,
                'notes' => 'Test order with NA grade',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'grade' => 'NA',
                        'quantity' => 10,
                        'price' => 50,
                    ]
                ]
            ]);

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }

        public function test_select_company_and_transport_placeholders_and_transporter_modal_is_blank(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $response = $this->withSession($session)->get('/sales/action');
            $response->assertStatus(200);

            // Verify Select Company has descriptive placeholder instead of "NA"
            $response->assertSee('-- Select Company --');

            // Verify Select Transport shows selectable "NA" when not selected
            $response->assertSee('<option value="" selected>NA</option>', false);

            // Verify app.js openAddTransportModal has blank input defaults
            $appJs = file_get_contents(public_path('js/app.js'));
            $this->assertStringContainsString('id="swal-trans-name" class="swal2-input" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="" placeholder="Enter transporter name"', $appJs);
            $this->assertStringContainsString('id="swal-trans-gst" class="swal2-input" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="" placeholder="Optional"', $appJs);
            $this->assertStringContainsString('id="swal-trans-code" class="swal2-input" value="" placeholder="+91"', $appJs);
        }

        public function test_sales_user_can_create_order_with_na_transporter(): void
        {
            $session = ['auth_user' => [
                'id' => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $response = $this->withSession($session)->postJson('/sales/action', [
                'company_id' => $this->company->id,
                'transporter_id' => 'NA',
                'notes' => 'Test order with NA transport',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'grade' => 'GRADE-A',
                        'quantity' => 15,
                        'price' => 120,
                    ]
                ]
            ]);

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            $order = Order::where('notes', 'Test order with NA transport')->first();
            $this->assertNotNull($order);
            $this->assertNull($order->transporter_id);
        }

        public function test_sales_history_renders_lr_copies_with_single_and_multiple_download_buttons(): void
        {
            $order = Order::create([
                'created_by'      => $this->salesUser->id,
                'company_id'      => $this->company->id,
                'transporter_id'  => $this->transporter->id,
                'total'           => 12000,
                'status'          => 'OPEN',
                'dispatch_status' => 'PARTIAL_DISPATCH',
            ]);

            $log1 = DispatchLog::create([
                'order_id'       => $order->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
                'lr_image_path'  => 'uploads/lr/test1.jpg',
                'lr_no'          => 'LR123',
            ]);

            $log2 = DispatchLog::create([
                'order_id'       => $order->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
                'lr_image_path'  => 'uploads/lr/test2.jpg',
                'lr_no'          => 'LR456',
            ]);

            $session = ['auth_user' => [
                'id'   => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            $response = $this->withSession($session)->get('/sales/history');
            $response->assertStatus(200);
            $content = $response->getContent();

            // Verify LR Copies section header and counts
            $this->assertStringContainsString('Dispatched Lorry Receipt (LR) Copies', $content);
            $this->assertStringContainsString('LR UPLOADED', $content);
            $this->assertStringContainsString('Download All (2) LRs (ZIP)', $content);

            // Verify individual LR copies with single download button
            $dsp1 = 'DSP-' . str_pad($log1->id, 4, '0', STR_PAD_LEFT);
            $dsp2 = 'DSP-' . str_pad($log2->id, 4, '0', STR_PAD_LEFT);
            $this->assertStringContainsString($dsp1, $content);
            $this->assertStringContainsString($dsp2, $content);
            $this->assertStringContainsString('/dispatch/download-lr/' . $log1->id, $content);
            $this->assertStringContainsString('/dispatch/download-lr/' . $log2->id, $content);
            $this->assertStringContainsString('Download LR', $content);
        }

        public function test_sales_user_can_download_single_and_multiple_lr_copies(): void
        {
            // Create dummy file for download
            $testDir = public_path('uploads/lr');
            if (!file_exists($testDir)) {
                mkdir($testDir, 0777, true);
            }
            $testFile = $testDir . '/test_download.jpg';
            file_put_contents($testFile, 'dummy image content');

            $order = Order::create([
                'created_by'      => $this->salesUser->id,
                'company_id'      => $this->company->id,
                'transporter_id'  => $this->transporter->id,
                'total'           => 12000,
                'status'          => 'OPEN',
                'dispatch_status' => 'DONE',
            ]);

            $log = DispatchLog::create([
                'order_id'       => $order->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
                'lr_image_path'  => 'uploads/lr/test_download.jpg',
                'lr_no'          => 'LR999',
            ]);

            $session = ['auth_user' => [
                'id'   => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            // 1. Download via /sales/dispatch/download-lr/{id}
            $res1 = $this->withSession($session)->get('/sales/dispatch/download-lr/' . $log->id);
            $res1->assertStatus(200);

            // 2. Download via /sales/download-lr/{id}
            $res2 = $this->withSession($session)->get('/sales/download-lr/' . $log->id);
            $res2->assertStatus(200);

            // 3. Download multiple via /sales/dispatch/download-multiple-lr?order_id={id}
            $res3 = $this->withSession($session)->get('/sales/dispatch/download-multiple-lr?order_id=' . $order->id);
            $res3->assertStatus(200);

            // Cleanup
            @unlink($testFile);
        }

        public function test_sales_history_updates_dispatched_and_pending_quantities_accurately(): void
        {
            $order = Order::create([
                'created_by'      => $this->salesUser->id,
                'company_id'      => $this->company->id,
                'transporter_id'  => $this->transporter->id,
                'total'           => 10000,
                'status'          => 'OPEN',
                'dispatch_status' => 'PENDING',
            ]);

            $orderItem = OrderItem::create([
                'order_id'       => $order->id,
                'product_id'     => $this->product->id,
                'grade'          => 'A',
                'quantity'       => 100,
                'price'          => 100,
                'dispatched_qty' => 0,
            ]);

            $session = ['auth_user' => [
                'id'   => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            // 1. Initial State: 100 kg ordered, 0 kg dispatched, 100 kg pending in accordion details
            $resp1 = $this->withSession($session)->get('/sales/history');
            $resp1->assertStatus(200);
            $content1 = $resp1->getContent();
            // Quantity badges removed from card header
            $this->assertStringNotContainsString('ORDER: <strong', $content1);
            $this->assertStringContainsString('>100 kg<', $content1);
            $this->assertStringContainsString('>0 kg<', $content1);
            $this->assertStringContainsString('PENDING', $content1);

            // 2. Partial Dispatch: dispatch 40 kg
            $dispatchLog = DispatchLog::create([
                'order_id'       => $order->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
            ]);
            \App\Models\DispatchLogItem::create([
                'dispatch_log_id' => $dispatchLog->id,
                'order_item_id'   => $orderItem->id,
                'quantity'        => 40,
            ]);

            $resp2 = $this->withSession($session)->get('/sales/history');
            $resp2->assertStatus(200);
            $content2 = $resp2->getContent();
            $this->assertStringContainsString('>40 kg<', $content2);
            $this->assertStringContainsString('>60 kg<', $content2);
            $this->assertStringContainsString('PARTIAL', $content2);

            // 3. Full Dispatch: dispatch remaining 60 kg
            $dispatchLog2 = DispatchLog::create([
                'order_id'       => $order->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
            ]);
            \App\Models\DispatchLogItem::create([
                'dispatch_log_id' => $dispatchLog2->id,
                'order_item_id'   => $orderItem->id,
                'quantity'        => 60,
            ]);

            $resp3 = $this->withSession($session)->get('/sales/history');
            $resp3->assertStatus(200);
            $content3 = $resp3->getContent();
            $this->assertStringContainsString('>100 kg<', $content3);
            $this->assertStringContainsString('FULLY DISPATCHED', $content3);
        }

        public function test_sales_home_displays_pending_and_partial_orders_and_stats(): void
        {
            $session = ['auth_user' => [
                'id'   => $this->salesUser->id,
                'name' => $this->salesUser->name,
                'role' => 'SALES',
            ]];

            // 1. Create a Pending Order
            $pendingOrder = Order::create([
                'created_by'      => $this->salesUser->id,
                'company_id'      => $this->company->id,
                'transporter_id'  => $this->transporter->id,
                'total'           => 5000,
                'status'          => 'OPEN',
                'dispatch_status' => 'PENDING',
                'due_date'        => now()->addDays(5)->toDateString(),
            ]);
            OrderItem::create([
                'order_id'       => $pendingOrder->id,
                'product_id'     => $this->product->id,
                'grade'          => 'A',
                'quantity'       => 50,
                'dispatched_qty' => 0,
                'rate'           => 100,
                'price'          => 100,
                'amount'         => 5000,
            ]);

            // 2. Create a Partial Order
            $partialOrder = Order::create([
                'created_by'      => $this->salesUser->id,
                'company_id'      => $this->company->id,
                'transporter_id'  => $this->transporter->id,
                'total'           => 8000,
                'status'          => 'OPEN',
                'dispatch_status' => 'PARTIAL',
                'due_date'        => now()->toDateString(), // Due today
            ]);
            $partialItem = OrderItem::create([
                'order_id'       => $partialOrder->id,
                'product_id'     => $this->product->id,
                'grade'          => 'A',
                'quantity'       => 80,
                'dispatched_qty' => 30,
                'rate'           => 100,
                'price'          => 100,
                'amount'         => 8000,
            ]);

            $dispatchLog = DispatchLog::create([
                'order_id'       => $partialOrder->id,
                'user_id'        => $this->salesUser->id,
                'transporter_id' => $this->transporter->id,
            ]);
            \App\Models\DispatchLogItem::create([
                'dispatch_log_id' => $dispatchLog->id,
                'order_item_id'   => $partialItem->id,
                'quantity'        => 30,
            ]);

            $res = $this->withSession($session)->get('/sales/home');
            $res->assertStatus(200);

            // Verify KPI Stat Cards
            $res->assertSee('Pending Orders');
            $res->assertSee('Partial Orders');
            $res->assertSee('Dispatched Orders');
            $res->assertSee('Due Today Orders');

            // Verify Tabs
            $res->assertSee('📋 All Open');
            $res->assertSee('⏳ Pending');
            $res->assertSee('📦 Partial');
            $res->assertSee('📅 Due Today');

            // Verify both pending and partial order contents are present
            $content = $res->getContent();
            $this->assertStringContainsString('#' . $pendingOrder->id, $content);
            $this->assertStringContainsString('#' . $partialOrder->id, $content);
            $this->assertStringContainsString('Disp: 30 kg', $content);
            $this->assertStringContainsString('Rem: 50 kg', $content);
        }
    }


