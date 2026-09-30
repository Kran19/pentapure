<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $cashierUser;
    protected User $rawUser;
    protected User $stockManagerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->cashierUser = User::create([
            'name' => 'Cashier One',
            'email' => 'cashier1@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->rawUser = User::create([
            'name' => 'Raw User',
            'email' => 'rawuser@example.com',
            'password' => 'password123',
            'role' => 'RAW',
            'status' => 'ACTIVE',
        ]);

        $this->stockManagerUser = User::create([
            'name' => 'Stock Manager User',
            'email' => 'sm@example.com',
            'password' => 'password123',
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        // Create some sample data
        $product = Product::create([
            'name' => 'Polymer Resin',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $location = \App\Models\Location::create(['name' => 'Main Warehouse']);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $this->rawUser->id,
            'stage' => 'RAW',
            'grade' => 'A',
            'location_id' => $location->id,
            'quantity' => 1000,
            'transaction_type' => 'IN',
        ]);

        Transaction::create([
            'user_id' => $this->cashierUser->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);
    }

    public function test_raw_history_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->rawUser->id,
            'name' => $this->rawUser->name,
            'role' => 'RAW',
        ]])->get('/raw/history/RAW/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_stock_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->post('/admin/stock/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_stock_pdf_download_with_stage_and_date_filters(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->post('/admin/stock/pdf', [
            'stages' => 'RAW,FINISHED',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_dispatch_activity_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->get('/admin/dispatch-activity/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_cashier_overview_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->get('/admin/cashier-overview/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_cashier_statement_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->cashierUser->id,
            'name' => $this->cashierUser->name,
            'role' => 'CASHIER',
        ]])->get('/cashier/history/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_direct_history_pdf_fallback_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->cashierUser->id,
            'name' => $this->cashierUser->name,
            'role' => 'CASHIER',
        ]])->get('/history/pdf?from=2026-09-18&to=2026-09-18&include_bills=yes&category=all&site=all&tab=personal');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_direct_history_cashier_pdf_fallback_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->cashierUser->id,
            'name' => $this->cashierUser->name,
            'role' => 'CASHIER',
        ]])->get('/history/cashier/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_stock_manager_dispatch_activity_pdf_download(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
            'permissions' => ['admin_dispatch_activity'],
        ]])->get('/stock_manager/dispatch-activity/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_dispatch_history_pdf_has_consistent_column_alignment_on_all_rows(): void
    {
        $company = Company::create(['name' => 'Test Alignment Corp']);
        $transporter = Transporter::create(['name' => 'Test Transporter', 'gst' => 'N/A']);
        $product = Product::first();

        $order = Order::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'created_by' => $this->adminUser->id,
            'status' => 'PENDING',
            'dispatch_status' => 'PENDING',
        ]);

        for ($i = 0; $i < 5; $i++) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 100 * ($i + 1),
                'price' => 50,
                'dispatched_qty' => 0,
            ]);
        }

        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->get('/admin/dispatch-activity/pdf?range=all');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify HTML table row columns match headers exactly
        $controller = app(\App\Http\Controllers\HistoryPdfController::class);
        $request = \Illuminate\Http\Request::create('/dispatch/history/pdf', 'GET', ['range' => 'all']);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('buildDispatchReportData');
        $method->setAccessible(true);
        $data = $method->invoke($controller, $request);

        $html = view('pdf.dispatch-history-report', $data)->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $tables = $dom->getElementsByTagName('table');
        $dataTable = null;
        foreach ($tables as $t) {
            if (strpos($t->getAttribute('class'), 'data-table') !== false) {
                $dataTable = $t;
                break;
            }
        }
        $this->assertNotNull($dataTable);

        $headerCount = $dataTable->getElementsByTagName('th')->length;
        $this->assertGreaterThan(0, $headerCount);

        foreach ($dataTable->getElementsByTagName('tbody') as $tbody) {
            $rows = $tbody->getElementsByTagName('tr');
            if ($rows->length === 0) {
                continue;
            }

            $firstRow = $rows->item(0);
            if ($firstRow->getAttribute('class') === 'total-row') {
                continue;
            }

            // Each order block must avoid page breaks inside to protect rowspan layout
            $this->assertStringContainsString('page-break-inside: avoid', (string) $tbody->getAttribute('style'));

            $firstRowTds = $firstRow->getElementsByTagName('td');
            $this->assertEquals($headerCount, $firstRowTds->length, 'First row of an order must contain full header count');

            if ($rows->length > 1) {
                // Verify order id and customer are merged with rowspan matching the item count
                $dispatchIdTd = $firstRowTds->item(0);
                $this->assertEquals((string) $rows->length, $dispatchIdTd->getAttribute('rowspan'), 'Dispatch ID should merge across all items of the order');

                $customerTd = $firstRowTds->item(4);
                $this->assertEquals((string) $rows->length, $customerTd->getAttribute('rowspan'), 'Customer cell should merge across all items of the order');

                // Subsequent item rows should contain only the 5 item-specific columns
                for ($r = 1; $r < $rows->length; $r++) {
                    $subRowTds = $rows->item($r)->getElementsByTagName('td');
                    $this->assertEquals(5, $subRowTds->length, 'Subsequent item rows should have item columns while order-level cells are merged via rowspan');
                }
            }
        }
    }
}
