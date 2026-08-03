<?php

namespace Tests\Feature;

use App\Models\Company;
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
            'gst' => '22AAAAA0000A1Z5',
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
        ]);

        $compResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('companies', ['name' => 'BETA LTD']);

        $transResponse = $this->withSession($session)->postJson('/sales/transport', [
            'name' => 'FAST FREIGHT',
            'gst' => 'N/A',
            'contact' => '8887776665',
        ]);

        $transResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('transporters', ['name' => 'FAST FREIGHT']);
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
}
