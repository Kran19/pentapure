<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryUsageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $stockManagerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->stockManagerUser = User::create([
            'name' => 'Stock Manager User',
            'email' => 'stock_manager@example.com',
            'password' => 'password123',
            'role' => 'STOCK_MANAGER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_category_in_use_cannot_be_deleted_from_stock_manager(): void
    {
        $category = Category::create([
            'name' => 'Packaging Material',
            'is_active' => true,
        ]);

        Transaction::create([
            'user_id' => $this->adminUser->id,
            'type' => 'OUT',
            'amount' => 500.00,
            'category' => 'Packaging Material',
            'date' => now(),
        ]);

        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
        ]];

        $response = $this->withSession($session)->deleteJson("/stock_manager/categories/{$category->id}");
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        // Check that view disables delete button and shows usage count badge
        $viewResponse = $this->withSession($session)->get('/stock_manager/categories');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('1 record');
        $viewResponse->assertSee('is-disabled');
    }

    public function test_category_in_use_by_slug_cannot_be_deleted(): void
    {
        $category = Category::create([
            'name' => 'Tea & Snacks',
            'is_active' => true,
        ]);

        Transaction::create([
            'user_id' => $this->adminUser->id,
            'type' => 'OUT',
            'amount' => 150.00,
            'category' => 'tea___snacks',
            'date' => now(),
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/categories/{$category->id}");
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = Category::create([
            'name' => 'Temporary Test Category',
            'is_active' => true,
        ]);

        $session = ['auth_user' => [
            'id' => $this->stockManagerUser->id,
            'name' => $this->stockManagerUser->name,
            'role' => 'STOCK_MANAGER',
        ]];

        $response = $this->withSession($session)->deleteJson("/stock_manager/categories/{$category->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
