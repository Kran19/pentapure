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
}
