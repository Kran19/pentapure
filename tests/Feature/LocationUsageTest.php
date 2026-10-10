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

    public function test_cold_storage_is_fixed_and_cannot_be_deleted(): void
    {
        $coldLoc = Location::firstOrCreate(['name' => 'Cold Storage']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$coldLoc->id}");
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Fixed system location (Cold Storage) cannot be deleted!'
        ]);
        $this->assertDatabaseHas('locations', ['id' => $coldLoc->id]);

        $viewResponse = $this->withSession($session)->get('/admin/locations');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Cold Storage');
        $viewResponse->assertSee('System Fixed');
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

    public function test_location_with_stock_cannot_be_deleted(): void
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('currently in use', $response->json('message'));

        // Shelf location must NOT be deleted
        $this->assertDatabaseHas('locations', ['id' => $shelf->id]);

        // Stock remains at that location
        $this->assertEquals($shelf->id, $stock->fresh()->location_id);

        // Also verify that the UI renders the delete button as disabled for in-use location
        $viewResponse = $this->withSession($session)->get('/admin/locations');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Cannot delete: Location is currently in use across 1 record');
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

    public function test_editing_location_updates_existing_record_without_creating_new_record(): void
    {
        Location::firstOrCreate(['name' => 'Main Warehouse']);
        $loc = Location::create(['name' => 'Rack Zone 9', 'description' => 'Original Zone 9']);
        $initialCount = Location::count();

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        // Edit via location_id
        $response = $this->withSession($session)->postJson('/admin/locations', [
            'location_id' => $loc->id,
            'name' => 'Rack Zone 9 Renamed',
            'description' => 'Updated Zone 9 notes',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // CRITICAL: Location count must NOT increase (no new record created)
        $this->assertEquals($initialCount, Location::count());
        $this->assertEquals('Rack Zone 9 Renamed', $loc->fresh()->name);
        $this->assertEquals('Updated Zone 9 notes', $loc->fresh()->description);

        // Edit via id alias
        $response2 = $this->withSession($session)->postJson('/admin/locations', [
            'id' => $loc->id,
            'name' => 'Rack Zone 9 Final',
            'description' => 'Final Zone 9 notes',
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);
        $this->assertEquals($initialCount, Location::count());
        $this->assertEquals('Rack Zone 9 Final', $loc->fresh()->name);
    }
}
