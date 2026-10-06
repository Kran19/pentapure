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
            'message' => 'Storage locations cannot be deleted once added. You can edit the location name or description instead.'
        ]);
        $this->assertDatabaseHas('locations', ['id' => $mainLoc->id]);
    }

    public function test_added_location_cannot_be_deleted(): void
    {
        $loc = Location::create(['name' => 'Cold Storage Bay']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $response = $this->withSession($session)->deleteJson("/admin/locations/{$loc->id}");
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Storage locations cannot be deleted once added. You can edit the location name or description instead.'
        ]);
        $this->assertDatabaseHas('locations', ['id' => $loc->id]);
    }

    public function test_locations_view_has_edit_button_and_no_delete_button(): void
    {
        $loc = Location::create(['name' => 'New Storage Area']);

        $session = ['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]];

        $viewResponse = $this->withSession($session)->get('/admin/locations');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('adminEditLocation');
        $viewResponse->assertDontSee('adminDeleteLocation');
        $viewResponse->assertDontSee('btn-icon delete');
    }
}
