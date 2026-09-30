<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagingCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::create([
            'name'     => 'Test Admin',
            'username' => 'admin_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => 'ADMIN',
            'branch'   => 'Main Branch',
            'status'   => 'ACTIVE',
        ]);
    }

    public function test_can_create_packaging_product(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $response = $this->withSession($session)->post('/admin/products', [
            'name' => '5-PLY CORRUGATED BOX',
            'type' => 'PACKAGING',
            'unit' => 'PCS',
            'rate' => 25.50,
            'threshold' => 100,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'name' => '5-PLY CORRUGATED BOX',
            'type' => 'PACKAGING',
            'unit' => 'PCS',
        ]);
    }

    public function test_admin_products_view_displays_packaging_materials_section(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        Product::create([
            'name' => 'STRETCH WRAP FILM',
            'type' => 'PACKAGING',
            'unit' => 'ROLL',
            'is_active' => true,
        ]);

        $response = $this->withSession($session)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('PACKAGING Materials');
        $response->assertSee('STRETCH WRAP FILM');
    }

    public function test_products_pdf_includes_packaging_materials(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        Product::create([
            'name' => 'PRINTED CARTON BOX',
            'type' => 'PACKAGING',
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        $response = $this->withSession($session)->get('/admin/products/pdf');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_can_adjust_stock_for_packaging_stage(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $product = Product::create([
            'name' => 'PACKAGING TAPE 2 INCH',
            'type' => 'PACKAGING',
            'unit' => 'ROLL',
            'is_active' => true,
        ]);

        $response = $this->withSession($session)->post('/admin/stock/adjust', [
            'product_id' => $product->id,
            'stage' => 'PACKAGING',
            'grade' => 'NONE',
            'quantity' => 50,
            'adjust_type' => 'set',
            'reason' => 'Initial stock inward',
            'location' => 'Main Warehouse',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'stage' => 'PACKAGING',
            'quantity' => 50,
        ]);
    }
}
