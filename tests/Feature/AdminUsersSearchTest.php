<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): array
    {
        $admin = User::factory()->create([
            'name' => 'Admin Manager',
            'username' => 'admin_mgr',
            'role' => 'ADMIN',
            'status' => 'ACTIVE'
        ]);
        return [
            'user' => $admin,
            'session' => ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']]
        ];
    }

    public function test_users_page_renders_search_bar(): void
    {
        $auth = $this->createAdmin();

        $response = $this->withSession($auth['session'])->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('id="userSearchInput"', false);
        $response->assertSee('id="userSearchClear"', false);
        $response->assertSee('id="userCountBadge"', false);
        $response->assertSee('filterUsersTable', false);
    }

    public function test_users_page_filters_by_search_query(): void
    {
        $auth = $this->createAdmin();

        $user1 = User::factory()->create([
            'name'     => 'Gulamabbas Burmavala',
            'username' => 'gulamabbas',
            'email'    => 'gulam@example.com',
            'phone'    => '+91 9876543210',
            'role'     => 'SUB_ADMIN',
            'status'   => 'ACTIVE'
        ]);

        $user2 = User::factory()->create([
            'name'     => 'Rajesh Kumar',
            'username' => 'rajeshk',
            'email'    => 'rajesh@example.com',
            'phone'    => '+91 9123456789',
            'role'     => 'STOCK_MANAGER',
            'status'   => 'ACTIVE'
        ]);

        // Search for Gulamabbas
        $response = $this->withSession($auth['session'])->get('/admin/users?search=Burmavala');
        $response->assertStatus(200);
        $response->assertSee('Gulamabbas Burmavala');
        $response->assertDontSee('Rajesh Kumar');

        // Search by username
        $response2 = $this->withSession($auth['session'])->get('/admin/users?search=rajeshk');
        $response2->assertStatus(200);
        $response2->assertSee('Rajesh Kumar');
        $response2->assertDontSee('Gulamabbas Burmavala');

        // Search by phone
        $response3 = $this->withSession($auth['session'])->get('/admin/users?search=9876543210');
        $response3->assertStatus(200);
        $response3->assertSee('Gulamabbas Burmavala');
        $response3->assertDontSee('Rajesh Kumar');

        // Search by non-existent term
        $response4 = $this->withSession($auth['session'])->get('/admin/users?search=NonExistentPersonXYZ');
        $response4->assertStatus(200);
        $response4->assertSee('No users found');
        $response4->assertSee('NonExistentPersonXYZ');
    }
}
