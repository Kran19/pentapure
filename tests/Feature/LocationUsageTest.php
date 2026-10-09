<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationUsageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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
    }

    public function test_main_warehouse_cannot_be_deleted(): void
    {
        $mainLoc = Location::firstOrCreate(['name' => 'Main Warehouse']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$mainLoc->id}");
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Fixed system location (Main Warehouse) cannot be deleted!'
        ]);
        $this->assertDatabaseHas('locations', ['id' => $mainLoc->id]);
    }

    public function test_added_location_can_be_deleted(): void
    {
        Location::firstOrCreate(['name' => 'Main Warehouse']);
        $loc = Location::create(['name' => 'Temporary Shelf Bay']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$loc->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseMissing('locations', ['id' => $loc->id]);
    }

    public function test_location_with_stock_reassigns_to_main_warehouse_and_deletes(): void
    {
        $mainWarehouse = Location::firstOrCreate(['name' => 'Main Warehouse']);
        $shelf = Location::create(['name' => 'Shelf Area B']);

        $product = Product::create([
            'name' => 'Test Powder',
            'type' => 'FINISHED',
            'unit' => 'KG',
        ]);

        $stock = Stock::create([
            'user_id' => $this->adminUser->id,
            'location_id' => $shelf->id,
            'product_id' => $product->id,
            'stage' => 'FINISHED',
            'grade' => 'NONE',
            'quantity' => 150,
            'transaction_type' => 'IN',
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$shelf->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Shelf location deleted
        $this->assertDatabaseMissing('locations', ['id' => $shelf->id]);

        // Stock reassigned to Main Warehouse
        $this->assertEquals($mainWarehouse->id, $stock->fresh()->location_id);
    }

    public function test_locations_view_has_edit_and_delete_buttons(): void
    {
        Location::firstOrCreate(['name' => 'Main Warehouse']);
        $loc = Location::create(['name' => 'New Storage Area']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $viewResponse = $this->withSession($session)->get('/admin/locations');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('adminEditLocation');
        $viewResponse->assertSee('adminDeleteLocation');
    }

    public function test_admin_can_edit_location(): void
    {
        Location::firstOrCreate(['name' => 'Main Warehouse']);
        $loc = Location::create(['name' => 'Warehouse Alpha', 'description' => 'Initial desc']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->postJson('/admin/locations', [
            'location_id' => $loc->id,
            'name' => 'Warehouse Alpha Updated',
            'description' => 'Updated desc',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('Warehouse Alpha Updated', $loc->fresh()->name);
    }

    public function test_admin_can_edit_location_keeping_same_name(): void
    {
        Location::firstOrCreate(['name' => 'Main Warehouse']);
        $loc = Location::create(['name' => 'Warehouse Beta', 'description' => 'Initial desc']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->postJson('/admin/locations', [
            'location_id' => $loc->id,
            'name' => 'Warehouse Beta',
            'description' => 'New Beta desc',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('New Beta desc', $loc->fresh()->description);
    }
}
