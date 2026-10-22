<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductGradeLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Product $product;
    protected Grade $gradeGold;
    protected Grade $gradeRegular;
    protected Location $location;

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

        $this->gradeGold = Grade::create(['name' => 'GOLD', 'is_active' => true]);
        $this->gradeRegular = Grade::create(['name' => 'REGULAR', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'TOMATO POWDER SD',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 100.00,
            'is_active' => true,
        ]);

        $this->product->grades()->sync([$this->gradeGold->id, $this->gradeRegular->id]);

        $this->location = Location::firstOrCreate(['name' => 'Main Warehouse']);
    }

    public function test_grade_with_active_stock_is_locked_in_products_view(): void
    {
        // Add stock in grade GOLD (120 KG)
        Stock::create([
            'user_id' => $this->adminUser->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'stage' => 'FINISHED',
            'grade' => 'GOLD',
            'quantity' => 120.0,
            'transaction_type' => 'IN',
            'date' => now(),
        ]);

        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->get('/admin/products');

        $response->assertStatus(200);
        $content = $response->getContent();

        // The page must contain TOMATO POWDER SD
        $this->assertStringContainsString('TOMATO POWDER SD', $content);

        // In the JSON passed to adminEditProduct, locked_grade_ids must contain gradeGold->id
        $this->assertStringContainsString('&quot;locked_grade_ids&quot;:[1]', $content);
        $this->assertStringContainsString('&quot;grade_stocks&quot;', $content);
    }

    public function test_backend_preserves_grade_with_active_stock_even_if_omitted_in_update(): void
    {
        // Add stock in grade GOLD (50 KG)
        Stock::create([
            'user_id' => $this->adminUser->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'stage' => 'FINISHED',
            'grade' => 'GOLD',
            'quantity' => 50.0,
            'transaction_type' => 'IN',
            'date' => now(),
        ]);

        // Attempt to update product while ONLY selecting REGULAR (omitting GOLD)
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/products', [
            'product_id' => $this->product->id,
            'name' => 'TOMATO POWDER SD',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 100.00,
            'threshold' => 0.00,
            'grades' => json_encode([$this->gradeRegular->id]), // omitting GOLD
            'allowed_roles' => json_encode(['ADMIN']),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // GOLD must STILL be attached because it has 50 KG stock!
        $this->product->refresh();
        $attachedGradeIds = $this->product->grades->pluck('id')->toArray();
        $this->assertContains($this->gradeGold->id, $attachedGradeIds, 'Grade with active stock must not be detached');
        $this->assertContains($this->gradeRegular->id, $attachedGradeIds);
    }

    public function test_grade_with_zero_stock_can_be_unselected(): void
    {
        // Neither GOLD nor REGULAR has stock.
        // Unselect REGULAR and keep only GOLD.
        $response = $this->withSession([
            'auth_user' => $this->adminUser->toArray(),
        ])->postJson('/admin/products', [
            'product_id' => $this->product->id,
            'name' => 'TOMATO POWDER SD',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'rate' => 100.00,
            'threshold' => 0.00,
            'grades' => json_encode([$this->gradeGold->id]), // REGULAR omitted
            'allowed_roles' => json_encode(['ADMIN']),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->product->refresh();
        $attachedGradeIds = $this->product->grades->pluck('id')->toArray();
        $this->assertContains($this->gradeGold->id, $attachedGradeIds);
        $this->assertNotContains($this->gradeRegular->id, $attachedGradeIds, 'Grade with 0 stock can be unselected');
    }
}
