<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagerEntryDateTimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $subAdmin;
    protected User $stockManager;
    protected Product $product;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subAdmin = User::create([
            'name' => 'STOCK & LR 920',
            'email' => 'subadmin920@pentapure.com',
            'username' => 'sub_admin',
            'password' => 'subadmin@123',
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => [
                'view_stock_manager_action',
                'edit_stock_manager_action',
                'view_stock_manager_history',
                'edit_stock_manager_history',
                'view_stock_manager_home',
                'view_stock_manager_stock',
            ],
        ]);

        $this->stockManager = User::create([
            'name' => 'Stock Manager User',
            'email' => 'sm_dt@pentapure.com',
            'username' => 'stock_manager',
            'password' => 'sm@123',
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $this->location = Location::firstOrCreate(['name' => 'AC ROOM']);

        $this->product = Product::firstOrCreate([
            'name' => 'DEHYDRATED ONION FLAKES',
        ], [
            'type' => 'FINISHED',
            'unit' => 'kg',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
    }

    public function test_sub_admin_stock_action_page_renders_entry_date_and_time_section(): void
    {
        $response = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->get('/sub_admin/stock-manager/action');

        $response->assertStatus(200);
        $response->assertSee('ENTRY DATE *', false);
        $response->assertSee('id="sm-entry-date"', false);
        $response->assertDontSee('id="sm-entry-time"', false);
        $response->assertDontSee('ENTRY TIME *', false);
        $response->assertSee('Live (Now)');
        $response->assertSee('Yesterday');
    }

    public function test_backdated_entry_without_entry_time_defaults_to_end_of_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 30, 0, 'Asia/Kolkata'));

        $response = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 150.0],
            ],
            'notes' => 'Yesterday inward without time',
            'entry_date' => '2026-11-17',
            'is_live_time' => false,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $stock = Stock::where('notes', 'Yesterday inward without time')->firstOrFail();
        $this->assertEquals('2026-11-17 23:59:00', $stock->date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-18 10:30:00', $stock->recorded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_live_mode_submission_records_current_ist_timestamp(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 16, 15, 25, 'Asia/Kolkata'));

        $response = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 100.0],
            ],
            'notes' => 'Current day live entry',
            'entry_date' => '2026-11-18',
            'entry_time' => '15:00', // Even if browser input had an older minute, is_live_time=true uses server now()
            'is_live_time' => true,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $stock = Stock::firstOrFail();
        $this->assertEquals('2026-11-18 16:15:25', $stock->date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-18 16:15:25', $stock->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-18 16:15:25', $stock->recorded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_backdated_next_day_inward_sets_selected_datetime_and_preserves_audit_timestamp(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 10, 30, 0, 'Asia/Kolkata'));

        // Sub Admin enters yesterday's missed inward (17 Nov at 05:45 PM) on 18 Nov morning
        $response = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 250.0],
            ],
            'notes' => 'Yesterday evening stock received',
            'entry_date' => '2026-11-17',
            'entry_time' => '17:45',
            'is_live_time' => false,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $stock = Stock::firstOrFail();
        $this->assertEquals('2026-11-17 17:45:00', $stock->date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-17 17:45:00', $stock->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-18 10:30:00', $stock->recorded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));

        // 1. History page shows effective date (17-11-2026, 05:45 PM) AND Entered audit subtitle (18-11-2026, 10:30 AM)
        $historyRes = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->get('/sub_admin/stock-manager/history');

        $historyRes->assertStatus(200);
        $historyRes->assertSee('17-11-2026, 05:45 PM');
        $historyRes->assertSee('Entered: 18-11-2026, 10:30 AM');

        // 2. Today's inward KPI on Stock Manager Home should be 0 (since stock belongs to 17 Nov, not 18 Nov)
        $homeRes = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->get('/stock_manager/home');
        $homeRes->assertStatus(200);
        $this->assertEquals(0.0, (float) $homeRes->viewData('pageData')['todayInward']);

        // 3. As-of-date Stock CSV report for 2026-11-17 includes the backdated 250 kg stock
        $csvRes = $this->withSession([
            'auth_user' => $this->stockManager->toArray(),
        ])->post('/stock_manager/stock/csv', [
            'stages' => 'FINISHED',
            'date' => '2026-11-17',
        ]);
        $csvRes->assertStatus(200);
        ob_start();
        $csvRes->sendContent();
        $csvContent = ob_get_clean();
        $this->assertStringContainsString('DEHYDRATED ONION FLAKES', $csvContent);
        $this->assertStringContainsString('250', $csvContent);

        Carbon::setTestNow();
    }

    public function test_backdated_outward_records_selected_past_datetime(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 11, 0, 0, 'Asia/Kolkata'));

        // Seed initial stock on 16 Nov
        $seed = new Stock([
            'product_id' => $this->product->id,
            'user_id' => $this->subAdmin->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'location_id' => $this->location->id,
            'quantity' => 300.0,
            'transaction_type' => 'IN',
            'date' => Carbon::create(2026, 11, 16, 10, 0, 0, 'Asia/Kolkata'),
            'notes' => 'Opening stock',
        ]);
        $seed->created_at = Carbon::create(2026, 11, 16, 10, 0, 0, 'Asia/Kolkata');
        $seed->save();

        // Backdate outward for 17 Nov at 06:20 PM
        $outRes = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/outward', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 80.0],
            ],
            'notes' => 'Yesterday evening outward',
            'entry_date' => '2026-11-17',
            'entry_time' => '18:20',
            'is_live_time' => false,
        ]);

        $outRes->assertStatus(200)->assertJson(['success' => true]);

        $outStock = Stock::where('transaction_type', 'OUT')->firstOrFail();
        $this->assertEquals('2026-11-17 18:20:00', $outStock->date->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-17 18:20:00', $outStock->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-18 11:00:00', $outStock->recorded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_future_date_or_future_time_today_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 14, 0, 0, 'Asia/Kolkata'));

        // 1. Future date (tomorrow)
        $futureDateRes = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 50.0],
            ],
            'entry_date' => '2026-11-19',
            'entry_time' => '10:00',
            'is_live_time' => false,
        ]);

        $futureDateRes->assertStatus(422);
        $this->assertEquals(0, Stock::count());

        // 2. Future time on current day (+2 hours ahead)
        $futureTimeRes = $this->withSession([
            'auth_user' => $this->subAdmin->toArray(),
        ])->postJson('/sub_admin/stock-manager/action', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'ALL',
            'location_splits' => [
                ['location' => 'AC ROOM', 'quantity' => 50.0],
            ],
            'entry_date' => '2026-11-18',
            'entry_time' => '16:00',
            'is_live_time' => false,
        ]);

        $futureTimeRes->assertStatus(422);
        $this->assertEquals(0, Stock::count());

        Carbon::setTestNow();
    }
}
