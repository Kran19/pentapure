<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LiveStockAccuracyAndZeroDataLossTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $subAdminUser;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_accuracy@pentapure.com',
            'password' => 'password123',
            'role'     => 'ADMIN',
            'status'   => 'ACTIVE',
        ]);

        $this->subAdminUser = User::create([
            'name'        => 'Sub Admin User',
            'email'       => 'subadmin_accuracy@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_dispatch_home', 'edit_dispatch_home', 'view_dispatch_action', 'edit_dispatch_action', 'module_dispatch'],
        ]);

        $this->location = Location::firstOrCreate(['name' => 'Main Warehouse']);
    }

    public function test_duplicate_product_stock_is_never_deleted_and_reassigned_to_primary(): void
    {
        // Create primary product
        $primary = Product::create([
            'name'      => 'HVP SOYA POWDER SD',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'rate'      => 200.0,
            'is_active' => true,
        ]);
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $primary->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 50.0,
            'transaction_type' => 'IN',
        ]);

        // Create duplicate product
        $duplicate = Product::create([
            'name'      => 'HVP SOYA POWDER SD',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'rate'      => 200.0,
            'is_active' => true,
        ]);
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $duplicate->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 30.0,
            'transaction_type' => 'IN',
        ]);

        // Total stocks count before visit is 2
        $this->assertEquals(2, DB::table('stocks')->count());

        // Visit admin stock page (which runs cleanupDuplicateProductsAndLimits)
        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->get('/admin/stock');
        $response->assertStatus(200);

        // Zero data loss check: Both stock records MUST be preserved and reassigned to primary
        $this->assertEquals(2, DB::table('stocks')->count(), 'No stock record should ever be deleted during duplicate cleanup');
        $this->assertEquals(2, DB::table('stocks')->where('product_id', $primary->id)->count());
        $this->assertEquals(0, DB::table('stocks')->where('product_id', $duplicate->id)->count());

        // Net stock of primary is 50 + 30 = 80
        $netStock = DB::table('stocks')
            ->where('product_id', $primary->id)
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
            ->value('net');
        $this->assertEquals(80.0, (float)$netStock);
    }

    public function test_negative_stock_is_not_hidden_from_live_stock_api(): void
    {
        $product = Product::create([
            'name'      => 'NEGATIVE TEST POWDER',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'is_active' => true,
        ]);

        // Create OUT transaction greater than IN (e.g. net -15 kg)
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 15.0,
            'transaction_type' => 'OUT',
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->get('/admin/stock/live');
        $response->assertStatus(200);

        $items = collect($response->json('data'));
        $item = $items->firstWhere('name', 'NEGATIVE TEST POWDER');

        $this->assertNotNull($item, 'Product with negative stock must not be hidden from live stock');
        $this->assertEquals(-15.0, (float)$item['quantity']);
    }

    public function test_location_breakdown_reflects_stock_accurately(): void
    {
        $locB = Location::firstOrCreate(['name' => 'Warehouse B']);

        $product = Product::create([
            'name'      => 'LOCATION TEST POWDER',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'is_active' => true,
        ]);

        // 100 kg IN at Main Warehouse
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 100.0,
            'transaction_type' => 'IN',
        ]);

        // 30 kg OUT at Main Warehouse
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 30.0,
            'transaction_type' => 'OUT',
        ]);

        // 50 kg IN at Warehouse B
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $locB->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 50.0,
            'transaction_type' => 'IN',
        ]);

        // Query location breakdown API
        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->getJson("/api/stock/locations?product_id={$product->id}&stage=FINISHED&grade=NONE");
        $response->assertStatus(200);

        $breakdown = collect($response->json('breakdown'));
        $mainLocEntry = $breakdown->firstWhere('name', 'Main Warehouse');
        $locBEntry = $breakdown->firstWhere('name', 'Warehouse B');

        $this->assertNotNull($mainLocEntry);
        $this->assertNotNull($locBEntry);
        // Main Warehouse: 100 - 30 = 70 kg
        $this->assertEquals(70.0, (float)$mainLocEntry['quantity']);
        // Warehouse B: 50 kg
        $this->assertEquals(50.0, (float)$locBEntry['quantity']);
    }

    public function test_dispatch_stock_map_and_readiness_recognize_hyphen_grade_as_none(): void
    {
        $product = Product::create([
            'name'      => 'HYPHEN GRADE TEST PRODUCT',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'is_active' => true,
        ]);

        // Inward recorded with grade '-'
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => '-',
            'quantity'         => 120.0,
            'transaction_type' => 'IN',
        ]);

        $company = Company::create(['name' => 'BETA CORP']);
        $transporter = Transporter::create(['name' => 'SAFE LOGISTICS']);

        // Order item requesting NONE grade
        $order = Order::create([
            'created_by'      => $this->subAdminUser->id,
            'company_id'      => $company->id,
            'transporter_id'  => $transporter->id,
            'total'           => 6000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
        ]);
        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'grade'          => 'NONE',
            'quantity'       => 100,
            'price'          => 60,
            'dispatched_qty' => 0,
        ]);

        // Dispatch details must recognize hyphen grade as NONE and show 120 kg availStock & READY_DISPATCH
        $detailsResp = $this->withSession(['auth_user' => $this->subAdminUser->toArray()])
            ->get("/sub_admin/dispatch/order-details/{$order->id}");
        $detailsResp->assertStatus(200);

        $this->assertEquals('READY_DISPATCH', $detailsResp->json('order.readiness'));
        $this->assertEquals(120.0, (float)$detailsResp->json('order.items.0.availStock'));
    }

    public function test_stock_deduct_falls_back_to_none_grade_stock_when_requested_grade_is_depleted(): void
    {
        $product = Product::create([
            'name'      => 'FALLBACK STOCK TEST PRODUCT',
            'type'      => 'FINISHED',
            'unit'      => 'KG',
            'is_active' => true,
        ]);

        // General stock 100 kg under NONE
        Stock::create([
            'user_id'          => $this->adminUser->id,
            'location_id'      => $this->location->id,
            'product_id'       => $product->id,
            'stage'            => 'FINISHED',
            'grade'            => 'NONE',
            'quantity'         => 100.0,
            'transaction_type' => 'IN',
        ]);

        // Deduct 40 kg under GRADE A
        Stock::deductStock($product->id, 'FINISHED', 'GRADE A', 40.0, $this->adminUser->id, 'Dispatch test');

        // General stock available should now be 60.0 kg (100 - 40)
        $netStock = DB::table('stocks')
            ->where('product_id', $product->id)
            ->where('stage', 'FINISHED')
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
            ->value('net');

        $this->assertEquals(60.0, (float)$netStock);
    }
}
