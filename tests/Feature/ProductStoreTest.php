<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_as_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'username' => 'admin_test',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $grade = Grade::create(['name' => 'A']);

        // 1. Create a new product
        $res = $this->withSession(['auth_user' => $admin->toArray()])
            ->post('/admin/products', [
                'name' => 'Test Product Raw',
                'type' => 'RAW',
                'unit' => 'KG',
                'rate' => '100.00',
                'threshold' => '10.00',
                'grades' => json_encode([$grade->id]),
                'allowed_roles' => json_encode(['ADMIN', 'STOCK_MANAGER']),
            ]);

        echo "\nAdmin Create Product Status: " . $res->status() . "\n";
        if ($res->status() !== 200) {
            echo "Response: " . $res->getContent() . "\n";
        }
        $res->assertStatus(200);

        $prod = Product::where('name', 'Test Product Raw')->first();
        $this->assertNotNull($prod);

        // 2. Update the product
        $resUpdate = $this->withSession(['auth_user' => $admin->toArray()])
            ->post('/admin/products', [
                'product_id' => $prod->id,
                'name' => 'Test Product Raw Updated',
                'type' => 'RAW',
                'unit' => 'KG',
                'rate' => '120.00',
                'threshold' => '15.00',
                'grades' => json_encode([$grade->id]),
                'allowed_roles' => json_encode(['ADMIN']),
            ]);

        echo "\nAdmin Update Product Status: " . $resUpdate->status() . "\n";
        if ($resUpdate->status() !== 200) {
            echo "Response: " . $resUpdate->getContent() . "\n";
        }
        $resUpdate->assertStatus(200);
    }

    public function test_store_product_as_stock_manager(): void
    {
        $stockManager = User::create([
            'name' => 'Stock Manager User',
            'email' => 'sm@test.com',
            'username' => 'sm_test',
            'password' => 'secret123',
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $res = $this->withSession(['auth_user' => $stockManager->toArray()])
            ->post('/stock_manager/products', [
                'name' => 'SM Product',
                'type' => 'RAW',
                'unit' => 'KG',
                'threshold' => '5.00',
            ]);

        echo "\nSM Create Product Status: " . $res->status() . "\n";
        if ($res->status() !== 200) {
            echo "Response: " . $res->getContent() . "\n";
        }
        $res->assertStatus(200);
    }

    public function test_sub_admin_view_only_cannot_modify_products(): void
    {
        $subAdmin = User::create([
            'name' => 'Sub Admin Viewer',
            'email' => 'subadmin_viewer@test.com',
            'username' => 'subadmin_viewer',
            'password' => 'secret123',
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_products'],
        ]);

        $product = Product::create([
            'name' => 'Existing Product',
            'type' => 'RAW',
            'unit' => 'KG',
            'rate' => '50.00',
            'threshold' => '5.00',
            'is_active' => true,
        ]);

        // 1. GET page works and is read-only
        $getView = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/products');
        $getView->assertStatus(200);
        $getView->assertDontSee('+ Add Product');
        $getView->assertDontSee('<th>Actions</th>', false);
        $getView->assertSee('Existing Product');
        $getView->assertSee('ACTIVE');

        // 2. Cannot Create
        $resCreate = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/products', [
                'name' => 'Should Not Create',
                'type' => 'RAW',
                'unit' => 'KG',
            ]);
        $resCreate->assertStatus(403);

        // 3. Cannot Edit
        $resEdit = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/products', [
                'product_id' => $product->id,
                'name' => 'Hacked Name',
                'type' => 'RAW',
                'unit' => 'KG',
            ]);
        $resEdit->assertStatus(403);

        // 4. Cannot Toggle status
        $resToggle = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/products/toggle/' . $product->id);
        $resToggle->assertStatus(403);

        // 5. Cannot Delete
        $resDelete = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->deleteJson('/sub_admin/products/' . $product->id);
        $resDelete->assertStatus(403);
    }
}
