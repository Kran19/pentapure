<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockManagerStockPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_stock_user_does_not_see_edit_buttons_and_mutation_is_forbidden()
    {
        // User with only view permission on stock_manager_stock
        $viewOnlyUser = User::create([
            'name' => 'View Only Stock Manager',
            'username' => 'view_stock_manager',
            'phone' => '+91 9898000091',
            'password' => Hash::make('password123'),
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_stock'],
        ]);

        $location = Location::firstOrCreate(['name' => 'Warehouse A']);

        $product = Product::create([
            'name' => 'CHILLI POWDER TEST',
            'type' => 'RAW',
            'unit' => 'KG',
            'threshold' => 10,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $viewOnlyUser->id,
            'location_id' => $location->id,
            'stage' => 'RAW',
            'grade' => 'A GRADE',
            'quantity' => 50,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession(['auth_user' => $viewOnlyUser->toArray()])
            ->get('/stock_manager/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must set window.isReadOnly = true
        $this->assertStringContainsString('window.isReadOnly = true;', $content);
        $this->assertStringContainsString('is-read-only', $content);

        // Edit buttons must NOT be rendered in initial Blade HTML table
        $this->assertStringNotContainsString("adminSetLimit('{$product->id}'", $content);

        // Client-side JS script must have guard for !window.isReadOnly
        $this->assertStringContainsString('${!window.isReadOnly ?', $content);

        // Mutation to update stock limit should be rejected with 403
        $postResponse = $this->withSession(['auth_user' => $viewOnlyUser->toArray()])
            ->postJson('/stock_manager/stock/limit', [
                'product_id' => $product->id,
                'stage' => 'RAW',
                'grade' => 'A GRADE',
                'alert_limit' => 25,
            ]);

        $postResponse->assertStatus(403);
    }

    public function test_edit_permitted_stock_user_sees_edit_buttons_and_can_mutate()
    {
        // User with edit permission on stock_manager_stock
        $editUser = User::create([
            'name' => 'Editor Stock Manager',
            'username' => 'edit_stock_manager',
            'phone' => '+91 9898000092',
            'password' => Hash::make('password123'),
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_stock', 'edit_stock_manager_stock'],
        ]);

        $location = Location::firstOrCreate(['name' => 'Warehouse B']);

        $product = Product::create([
            'name' => 'TURMERIC POWDER TEST',
            'type' => 'RAW',
            'unit' => 'KG',
            'threshold' => 10,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $editUser->id,
            'location_id' => $location->id,
            'stage' => 'RAW',
            'grade' => 'A GRADE',
            'quantity' => 100,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession(['auth_user' => $editUser->toArray()])
            ->get('/stock_manager/stock');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must set window.isReadOnly = false
        $this->assertStringContainsString('window.isReadOnly = false;', $content);
        $this->assertStringNotContainsString('body class="admin-mode is-read-only', $content);

        // Edit buttons should be rendered in table
        $this->assertStringContainsString("adminSetLimit('{$product->id}'", $content);

        // Mutation should be permitted
        $postResponse = $this->withSession(['auth_user' => $editUser->toArray()])
            ->postJson('/stock_manager/stock/limit', [
                'product_id' => $product->id,
                'stage' => 'RAW',
                'grade' => 'A GRADE',
                'alert_limit' => 25,
            ]);

        $postResponse->assertStatus(200);
        $postResponse->assertJson(['success' => true]);
    }
}
