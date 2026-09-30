<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDispatchActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_dispatch_activity_renders_card_layout_and_status_tabs(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $company = Company::create(['name' => 'Earth Expo', 'address' => 'Some address']);
        $transporter = Transporter::create(['name' => 'New Truck', 'phone' => '1234567890']);
        $product = Product::create([
            'name' => 'Dehydrated Garlic Powder',
            'type' => 'FINISHED',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $order = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 3693202.00,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'date' => now()->toDateTimeString(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'grade' => 'GOLD',
            'quantity' => 450.00,
            'dispatched_qty' => 0.00,
            'price' => 100.00,
        ]);

        $response = $this->withSession($session)->get('/admin/dispatch-activity');

        $response->assertStatus(200);
        $response->assertSee('ORDER #' . $order->id . ' - EARTH EXPO');
        $response->assertSee('NEW TRUCK');
        $response->assertSee('SALES BY:');
        $response->assertSee('₹3,693,202.00');
        $response->assertSee('Order PDF');
        $response->assertSee('/admin/sales/order/pdf/' . $order->id);
        $response->assertSee('Pending');
        $response->assertSee('Partial');
        $response->assertSee('All');
        $response->assertSee('Fully Dispatched');
        $response->assertSee('(FG)');
        $response->assertSee('ORDER:');
        $response->assertSee('450 kg');
    }

    public function test_admin_dispatch_activity_status_filtering(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $company = Company::create(['name' => 'Alpha Corp']);
        $transporter = Transporter::create(['name' => 'Express Cargo']);

        $pendingOrder = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 1000.00,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
        ]);

        $partialOrder = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 2000.00,
            'status' => 'APPROVED',
            'dispatch_status' => 'PARTIAL',
        ]);

        // Filter Pending
        $resPending = $this->withSession($session)->get('/admin/dispatch-activity?status=PENDING');
        $resPending->assertStatus(200);
        $resPending->assertSee('ORDER #' . $pendingOrder->id . ' - ALPHA CORP');
        $resPending->assertDontSee('ORDER #' . $partialOrder->id . ' - ALPHA CORP');

        // Filter Partial
        $resPartial = $this->withSession($session)->get('/admin/dispatch-activity?status=PARTIAL');
        $resPartial->assertStatus(200);
        $resPartial->assertSee('ORDER #' . $partialOrder->id . ' - ALPHA CORP');
        $resPartial->assertDontSee('ORDER #' . $pendingOrder->id . ' - ALPHA CORP');
    }

    public function test_admin_dispatch_activity_search_filtering(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $companyA = Company::create(['name' => 'Zeta Logistics']);
        $companyB = Company::create(['name' => 'Beta Food']);

        $orderA = Order::create([
            'created_by' => $admin->id,
            'company_id' => $companyA->id,
            'total' => 500.00,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
        ]);

        $orderB = Order::create([
            'created_by' => $admin->id,
            'company_id' => $companyB->id,
            'total' => 800.00,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
        ]);

        $res = $this->withSession($session)->get('/admin/dispatch-activity?q=Zeta');
        $res->assertStatus(200);
        $res->assertSee('ORDER #' . $orderA->id . ' - ZETA LOGISTICS');
        $res->assertDontSee('ORDER #' . $orderB->id . ' - BETA FOOD');
    }
}
