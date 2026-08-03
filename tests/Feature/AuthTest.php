<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_created_by_admin_can_login_with_correct_password(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'user_id' => $admin->id,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/home');
        $this->assertEquals($admin->id, session('auth_user')['id']);
    }

    public function test_admin_can_create_new_user_and_new_user_can_login(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->withSession(['auth_user' => [
            'id' => $admin->id,
            'name' => $admin->name,
            'role' => 'ADMIN',
            'permissions' => [],
        ]]);

        $response = $this->post('/admin/users', [
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'password' => 'cashier123',
            'role' => 'CASHIER',
        ]);

        $response->assertJson(['success' => true]);

        $cashier = User::where('email', 'cashier@example.com')->first();
        $this->assertNotNull($cashier);

        // Prove cashier password is hash checked correctly
        $this->assertTrue(Hash::check('cashier123', $cashier->password));

        // Test login as new cashier
        $loginResponse = $this->post('/login', [
            'user_id' => $cashier->id,
            'password' => 'cashier123',
        ]);

        $loginResponse->assertRedirect('/cashier/home');
    }

    public function test_unauthorized_role_cannot_access_admin_users(): void
    {
        $cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier2@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->withSession(['auth_user' => [
            'id' => $cashier->id,
            'name' => $cashier->name,
            'role' => 'CASHIER',
            'permissions' => [],
        ]]);

        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }
}
