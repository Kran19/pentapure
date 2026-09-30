<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearPurchaseOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_purchase_orders_command_clears_pos_and_preserves_users_and_products(): void
    {
        $initialUserCount = User::count();
        $initialProductCount = Product::count();

        $user = User::create([
            'name' => 'Stock Person',
            'email' => 'stock_extra@example.com',
            'password' => 'secret123',
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Apple Powder SD',
            'type' => 'FINISHED',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5000,
            'note' => 'Test PO',
            'status' => 'RECEIVED',
            'date' => now(),
        ]);

        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertEquals($initialProductCount + 1, Product::count());

        $this->artisan('po:clear-data', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertEquals($initialProductCount + 1, Product::count());
    }
}
