<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdversarialTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_role_cannot_post_to_admin_endpoints(): void
    {
        $salesUser = User::create(['name' => 'Sales User', 'email' => 'sales1@ex.com', 'password' => 'pass', 'role' => 'SALES', 'status' => 'ACTIVE']);
        $session = ['auth_user' => ['id' => $salesUser->id, 'name' => $salesUser->name, 'role' => 'SALES']];

        $response = $this->withSession($session)->postJson('/admin/users', [
            'name' => 'Attacker Admin',
            'email' => 'attacker@ex.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $response->assertStatus(403);
    }

    public function test_cashier_role_cannot_manage_categories(): void
    {
        $cashierUser = User::create(['name' => 'Cashier User', 'email' => 'cashier1@ex.com', 'password' => 'pass', 'role' => 'CASHIER', 'status' => 'ACTIVE']);
        $session = ['auth_user' => ['id' => $cashierUser->id, 'name' => $cashierUser->name, 'role' => 'CASHIER']];

        $response = $this->withSession($session)->postJson('/admin/categories', [
            'name' => 'Forged Category',
        ]);

        $response->assertStatus(403);
    }

    public function test_raw_role_cannot_execute_dispatch(): void
    {
        $rawUser = User::create(['name' => 'Raw User', 'email' => 'raw1@ex.com', 'password' => 'pass', 'role' => 'RAW', 'status' => 'ACTIVE']);
        $session = ['auth_user' => ['id' => $rawUser->id, 'name' => $rawUser->name, 'role' => 'RAW']];

        $response = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => 1,
            'items' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_cashier_cannot_view_bill_of_unauthorized_cashier(): void
    {
        $cashierA = User::create(['name' => 'Cashier A', 'email' => 'ca@ex.com', 'password' => 'pass', 'role' => 'CASHIER', 'status' => 'ACTIVE', 'visible_cashiers' => []]);
        $cashierC = User::create(['name' => 'Cashier C', 'email' => 'cc@ex.com', 'password' => 'pass', 'role' => 'CASHIER', 'status' => 'ACTIVE']);

        $txC = Transaction::create(['user_id' => $cashierC->id, 'type' => 'IN', 'amount' => 500, 'category' => 'sales']);
        $billC = TransactionBill::create([
            'transaction_id' => $txC->id,
            'file_path' => 'bills/test.jpg',
            'file_type' => 'image',
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 0,
        ]);

        $sessionA = ['auth_user' => ['id' => $cashierA->id, 'name' => $cashierA->name, 'role' => 'CASHIER']];

        $response = $this->withSession($sessionA)->get("/cashier/bill/{$billC->id}/view");
        $response->assertStatus(403);
    }

    public function test_consecutive_dispatches_prevent_negative_stock(): void
    {
        $dispatchUser = User::create(['name' => 'Dispatch Officer', 'email' => 'do@ex.com', 'password' => 'pass', 'role' => 'DISPATCH', 'status' => 'ACTIVE']);
        $company = Company::create(['name' => 'CORP X', 'contact' => '+91 9999900000', 'address' => 'Site X']);
        $transporter = Transporter::create(['name' => 'LOGISTICS Y', 'contact' => '+91 8888800000']);
        $product = Product::create(['name' => 'Finished Pipe 90mm', 'type' => 'FINISHED', 'unit' => 'm', 'is_active' => true]);

        $location = \App\Models\Location::create(['name' => 'Default Store']);

        // Add exactly 100m stock
        Stock::create([
            'product_id' => $product->id,
            'user_id' => $dispatchUser->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $location->id,
            'quantity' => 100,
            'transaction_type' => 'IN',
        ]);

        $order = Order::create([
            'created_by' => $dispatchUser->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 5000,
            'status' => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        $item1 = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'grade' => 'NONE', 'quantity' => 80, 'price' => 50]);

        $session = ['auth_user' => ['id' => $dispatchUser->id, 'name' => $dispatchUser->name, 'role' => 'DISPATCH']];

        // Request A: 80m (Succeeds)
        $resA = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $order->id,
            'items' => [['order_item_id' => $item1->id, 'quantity' => 80]],
        ]);
        $resA->assertJson(['success' => true]);

        // Request B: 80m (Fails because stock remaining is 20m)
        $resB = $this->withSession($session)->postJson('/dispatch/action', [
            'order_id' => $order->id,
            'items' => [['order_item_id' => $item1->id, 'quantity' => 80]],
        ]);
        $resB->assertStatus(422);

        // Verify stock did not go negative
        $finalStock = $product->currentStock('FINISHED');
        $this->assertEquals(20, $finalStock);
    }
}
