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
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('locations', ['id' => $mainLoc->id]);
    }

    public function test_location_in_use_cannot_be_deleted(): void
    {
        $loc = Location::create(['name' => 'Cold Storage Bay']);
        $product = Product::create([
            'name' => 'Test Product',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'user_id' => $this->adminUser->id,
            'stage' => 'RAW',
            'grade' => 'NONE',
            'location_id' => $loc->id,
            'quantity' => 50,
            'transaction_type' => 'IN',
            'date' => now(),
        ]);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$loc->id}");
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('locations', ['id' => $loc->id]);

        // Check that view disables delete button and shows usage count
        $viewResponse = $this->withSession($session)->get('/admin/locations');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('1 record');
        $viewResponse->assertSee('is-disabled');
    }

    public function test_unused_location_can_be_deleted(): void
    {
        $loc = Location::create(['name' => 'Temporary Spare Shelf']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$loc->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('locations', ['id' => $loc->id]);
    }
}
