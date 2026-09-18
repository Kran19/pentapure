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
}
