<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pentapure.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->product = Product::create([
            'name' => 'TOMATO POWDER SD',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 100.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_rate_for_individual_stage_and_grade(): void
    {
        $location = \App\Models\Location::create(['name' => 'Main Warehouse']);

        // Add stock in RAW and FINISHED (grades: TPS and A)
        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $location->id,
            'product_id' => $this->product->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'quantity' => 50,
            'transaction_type' => 'IN',
        ]);

        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $location->id,
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'TPS',
            'quantity' => 100,
            'transaction_type' => 'IN',
        ]);

        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $location->id,
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'GRADE A',
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        // 1. Update rate specifically for FINISHED - TPS to 350.00
        $response = $this->withSession($session)->postJson('/admin/stock/rate', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'TPS',
            'rate' => 350.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check StockLimit table
        $this->assertDatabaseHas('stock_limits', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'TPS',
            'rate' => 350.00,
        ]);

        // 2. Fetch live stock API to ensure only FINISHED - TPS changed to 350, while others remain default product rate 100
        $liveResponse = $this->withSession($session)->get('/admin/stock/live');
        $liveResponse->assertStatus(200);
        $liveData = $liveResponse->json('data');

        $tpsRow = collect($liveData)->first(fn($item) => $item['stage'] === 'FINISHED' && $item['grade'] === 'TPS');
        $gradeARow = collect($liveData)->first(fn($item) => $item['stage'] === 'FINISHED' && $item['grade'] === 'GRADE A');
        $rawRow = collect($liveData)->first(fn($item) => $item['stage'] === 'RAW');

        $this->assertNotNull($tpsRow);
        $this->assertNotNull($gradeARow);
        $this->assertNotNull($rawRow);

        $this->assertEquals(350.00, (float)$tpsRow['rate']);
        $this->assertEquals(100.00, (float)$gradeARow['rate']);
        $this->assertEquals(100.00, (float)$rawRow['rate']);

        // 3. Now update RAW stage rate to 120.00
        $response2 = $this->withSession($session)->postJson('/admin/stock/rate', [
            'product_id' => $this->product->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'rate' => 120.00,
        ]);
        $response2->assertStatus(200);

        // Fetch live stock API again
        $liveResponse2 = $this->withSession($session)->get('/admin/stock/live');
        $liveData2 = $liveResponse2->json('data');

        $tpsRow2 = collect($liveData2)->first(fn($item) => $item['stage'] === 'FINISHED' && $item['grade'] === 'TPS');
        $gradeARow2 = collect($liveData2)->first(fn($item) => $item['stage'] === 'FINISHED' && $item['grade'] === 'GRADE A');
        $rawRow2 = collect($liveData2)->first(fn($item) => $item['stage'] === 'RAW');

        $this->assertEquals(350.00, (float)$tpsRow2['rate']);
        $this->assertEquals(100.00, (float)$gradeARow2['rate']);
        $this->assertEquals(120.00, (float)$rawRow2['rate']);

        // 4. Verify admin stock page renders the updated rates
        $pageResponse = $this->withSession($session)->get('/admin/stock');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('₹350.00');
        $pageResponse->assertSee('₹120.00');
    }

    public function test_admin_stock_pdf_and_csv_reflect_individual_rates(): void
    {
        $location = \App\Models\Location::create(['name' => 'Main Warehouse']);

        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $location->id,
            'product_id' => $this->product->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'quantity' => 10,
            'transaction_type' => 'IN',
        ]);

        Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $location->id,
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'TPS',
            'quantity' => 20,
            'transaction_type' => 'IN',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        // Set custom rate for FINISHED - TPS = 200, RAW remains default 100
        $this->withSession($session)->postJson('/admin/stock/rate', [
            'product_id' => $this->product->id,
            'stage' => 'FINISHED',
            'grade' => 'TPS',
            'rate' => 200.00,
        ])->assertStatus(200);

        // PDF test
        $pdfResponse = $this->withSession($session)->post('/admin/stock/pdf', [
            'stages' => ['RAW', 'FINISHED'],
        ]);
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('%PDF', $pdfResponse->getContent());

        // CSV test
        $csvResponse = $this->withSession($session)->get('/admin/stock/csv?stages=RAW,FINISHED');
        $csvResponse->assertStatus(200);
        $csvContent = $csvResponse->streamedContent();

        // FINISHED row should have 200 rate, RAW row should have 100 rate
        $this->assertStringContainsString('200', $csvContent);
        $this->assertStringContainsString('100', $csvContent);
    }

    public function test_updating_rate_without_stage_updates_base_product(): void
    {
        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->postJson('/admin/stock/rate', [
            'product_id' => $this->product->id,
            'rate' => 450.00,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(450.00, (float)$this->product->fresh()->rate);
    }
}
