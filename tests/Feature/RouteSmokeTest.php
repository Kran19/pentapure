<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::create([
            'name' => "User {$role}",
            'email' => strtolower($role) . '@example.com',
            'password' => 'password123',
            'role' => $role,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_routes_return_200(): void
    {
        $admin = $this->createUser('ADMIN');
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $routes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/products',
            '/admin/stock',
            '/admin/po',
            '/admin/logs',
            '/admin/cashier-logs',
            '/admin/grades',
            '/admin/notifications',
            '/admin/categories',
            '/admin/dispatch-activity',
            '/admin/cashier-overview',
        ];

        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_raw_routes_return_200(): void
    {
        $raw = $this->createUser('RAW');
        $session = ['auth_user' => ['id' => $raw->id, 'name' => $raw->name, 'role' => 'RAW']];

        $routes = ['/raw/home', '/raw/action', '/raw/po', '/raw/history', '/raw/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_semi_routes_return_200(): void
    {
        $semi = $this->createUser('SEMI');
        $session = ['auth_user' => ['id' => $semi->id, 'name' => $semi->name, 'role' => 'SEMI']];

        $routes = ['/semi/home', '/semi/action', '/semi/po', '/semi/history', '/semi/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_finished_routes_return_200(): void
    {
        $finished = $this->createUser('FINISHED');
        $session = ['auth_user' => ['id' => $finished->id, 'name' => $finished->name, 'role' => 'FINISHED']];

        $routes = ['/finished/home', '/finished/action', '/finished/po', '/finished/history', '/finished/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_sales_routes_return_200(): void
    {
        $sales = $this->createUser('SALES');
        $session = ['auth_user' => ['id' => $sales->id, 'name' => $sales->name, 'role' => 'SALES']];

        $routes = ['/sales/home', '/sales/action', '/sales/history', '/sales/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_dispatch_routes_return_200(): void
    {
        $dispatch = $this->createUser('DISPATCH');
        $session = ['auth_user' => ['id' => $dispatch->id, 'name' => $dispatch->name, 'role' => 'DISPATCH']];

        $routes = ['/dispatch/home', '/dispatch/action', '/dispatch/history', '/dispatch/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }

    public function test_cashier_routes_return_200(): void
    {
        $cashier = $this->createUser('CASHIER');
        $session = ['auth_user' => ['id' => $cashier->id, 'name' => $cashier->name, 'role' => 'CASHIER']];

        $routes = ['/cashier/home', '/cashier/action', '/cashier/history', '/cashier/ledger', '/cashier/profile'];
        foreach ($routes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(200);
        }
    }
}
