<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_admin_with_stock_manager_stock_can_access_stock_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Stock Sub Admin',
            'username' => 'stock_subadmin',
            'phone' => '+91 9898000010',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(200);
        $response->assertSee('Live Stock');
    }

    public function test_sub_admin_with_admin_stock_can_access_stock_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Admin Stock Sub Admin',
            'username' => 'admin_stock_subadmin',
            'phone' => '+91 9898000011',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(200);
        $response->assertSee('Live Stock');
    }

    public function test_sub_admin_with_stock_manager_action_can_access_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Action Sub Admin',
            'username' => 'action_subadmin',
            'phone' => '+91 9898000012',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_action']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock-manager/action');

        $response->assertStatus(200);
        $response->assertSee('Stock Action');
    }

    public function test_sub_admin_with_cashier_ledger_can_access_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Cashier Sub Admin',
            'username' => 'cashier_subadmin',
            'phone' => '+91 9898000013',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_cashier_ledger']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/cashier/ledger');

        $response->assertStatus(200);
        $response->assertSee('Cashier Ledger');
    }

    public function test_sub_admin_without_stock_permission_cannot_access_stock_and_no_stock_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'No Stock Sub Admin',
            'username' => 'nostock_subadmin',
            'phone' => '+91 9898000014',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_cashier_ledger']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(403);
    }

    public function test_sub_admin_can_submit_inward_action_via_sub_admin_action_route()
    {
        $product = \App\Models\Product::create([
            'name' => 'Test Amchur Giant',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        \App\Models\Location::firstOrCreate(['name' => 'Cold Storage']);

        $subAdmin = User::create([
            'name' => 'Inward Sub Admin',
            'username' => 'inward_subadmin',
            'phone' => '+91 9898000015',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_action', 'edit_stock_manager_action']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/action', [
                'product_id' => $product->id,
                'stage' => 'RAW',
                'grade' => 'ALL',
                'location_splits' => [
                    ['location' => 'Cold Storage', 'quantity' => 200]
                ],
                'notes' => 'Testing inward via /sub_admin/action'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_sub_admin_can_submit_inward_action_via_sub_admin_stock_manager_action_route()
    {
        $product = \App\Models\Product::create([
            'name' => 'Test Amchur Giant 2',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        \App\Models\Location::firstOrCreate(['name' => 'Cold Storage']);

        $subAdmin = User::create([
            'name' => 'Inward Sub Admin 2',
            'username' => 'inward_subadmin2',
            'phone' => '+91 9898000016',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_action', 'edit_stock_manager_action']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/stock-manager/action', [
                'product_id' => $product->id,
                'stage' => 'RAW',
                'grade' => 'ALL',
                'location_splits' => [
                    ['location' => 'Cold Storage', 'quantity' => 150]
                ],
                'notes' => 'Testing inward via /sub_admin/stock-manager/action'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 150,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_sub_admin_can_submit_outward_action_via_sub_admin_outward_route()
    {
        $product = \App\Models\Product::create([
            'name' => 'Test Amchur Giant Outward',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $loc = \App\Models\Location::firstOrCreate(['name' => 'Cold Storage']);

        $subAdmin = User::create([
            'name' => 'Outward Sub Admin',
            'username' => 'outward_subadmin',
            'phone' => '+91 9898000017',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_action', 'edit_stock_manager_action']
        ]);

        // Seed initial IN stock
        \App\Models\Stock::create([
            'product_id' => $product->id,
            'stage' => 'RAW',
            'grade' => 'ALL',
            'location_id' => $loc->id,
            'quantity' => 500,
            'transaction_type' => 'IN',
            'user_id' => $subAdmin->id,
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/outward', [
                'product_id' => $product->id,
                'stage' => 'RAW',
                'grade' => 'ALL',
                'location_splits' => [
                    ['location' => 'Cold Storage', 'quantity' => 100]
                ],
                'notes' => 'Testing outward via /sub_admin/outward'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 100,
            'transaction_type' => 'OUT',
        ]);
    }

    public function test_sub_admin_with_all_stock_manager_permissions_sees_all_8_pages_in_sidebar()
    {
        $perms = [
            'view_stock_manager_home',
            'view_stock_manager_action',
            'view_stock_manager_stock',
            'view_stock_manager_po',
            'view_stock_manager_history',
            'view_stock_manager_products',
            'view_stock_manager_grades',
            'view_stock_manager_locations',
        ];

        $subAdmin = User::create([
            'name' => 'All Stock Sub Admin',
            'username' => 'all_stock_subadmin',
            'phone' => '+91 9898000018',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => $perms,
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock-manager/home');

        $response->assertStatus(200);
        $response->assertSee('Stock Manager Panel');
        $response->assertSee('Stock Manager Home');
        $response->assertSee('Stock Action');
        $response->assertSee('Live Stock View');
        $response->assertSee('Purchase Orders');
        $response->assertSee('Stock Manager History');
        $response->assertSee('Products Master');
        $response->assertSee('Grades Master');
        $response->assertSee('Storage Location');

        // Unpermitted panels should not show
        $response->assertDontSee('Sales Panel');
        $response->assertDontSee('Dispatch Panel');
        $response->assertDontSee('Cashier Panel');
    }

    public function test_sub_admin_with_dispatch_panel_permissions_sees_all_4_dispatch_pages_in_sidebar()
    {
        $perms = [
            'view_dispatch_home',
            'view_dispatch_action',
            'view_dispatch_history',
            'view_dispatch_report',
        ];

        $subAdmin = User::create([
            'name' => 'Dispatch Sub Admin',
            'username' => 'dispatch_subadmin',
            'phone' => '+91 9898000019',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => $perms,
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/dispatch/home');

        $response->assertStatus(200);
        $response->assertSee('Dispatch Panel');
        $response->assertSee('Dispatch Dashboard');
        $response->assertSee('Dispatch Action / Entry');
        $response->assertSee('Dispatch History');
        $response->assertSee('Order Report');

        // Stock Manager and Cashier panels should not show
        $response->assertDontSee('Stock Manager Panel');
        $response->assertDontSee('Cashier Panel');
    }

    public function test_sub_admin_home_shows_stock_manager_dashboard()
    {
        $subAdmin = User::create([
            'name' => 'Sub Admin Home User',
            'username' => 'subadmin_home_user',
            'phone' => '+91 9898000020',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_home']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/home');

        $response->assertStatus(200);
        $response->assertSee('Stock Manager Dashboard');
        $response->assertSee('Stock Inward / Outward');
        $response->assertSee('Live Stock Panel');
    }

    public function test_sub_admin_login_redirects_to_sub_admin_home()
    {
        $subAdmin = User::create([
            'name' => 'Login Sub Admin',
            'username' => 'login_subadmin',
            'phone' => '+91 9898000021',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_home']
        ]);

        $response = $this->post('/login', [
            'username' => 'login_subadmin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/sub_admin/home');
    }

    public function test_sub_admin_can_access_dispatch_report_and_download_pdf()
    {
        $subAdmin = User::create([
            'name' => 'Dispatch Report Sub Admin',
            'username' => 'dispatch_report_subadmin',
            'phone' => '+91 9898000099',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_dispatch_report']
        ]);

        $company = \App\Models\Company::create(['name' => 'ACME CORP']);
        $product = \App\Models\Product::create(['name' => 'Pipe 50mm', 'type' => 'FINISHED', 'unit' => 'm', 'is_active' => true]);

        $order = \App\Models\Order::create([
            'created_by'      => $subAdmin->id,
            'company_id'      => $company->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);

        \App\Models\OrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'grade'      => 'A',
            'quantity'   => 10,
            'price'      => 100,
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/dispatch/report');

        $response->assertStatus(200);
        $response->assertSee('Order Report');

        $pdfResp = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/history/dispatch/pdf?range=all&status=PENDING');
        $pdfResp->assertStatus(200);
        $pdfResp->assertHeader('Content-Type', 'application/pdf');
    }
}

