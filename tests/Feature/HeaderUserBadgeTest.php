<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HeaderUserBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_header_displays_name_and_admin_badge()
    {
        $admin = User::create([
            'name' => 'SUPER ADMIN',
            'username' => 'super_admin',
            'phone' => '+91 9999000001',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withSession(['auth_user' => $admin->toArray()])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('SUPER ADMIN');
        $response->assertSee('ADMIN');
    }

    public function test_sub_admin_header_displays_name_and_sub_admin_badge()
    {
        $subAdmin = User::create([
            'name' => 'SUB ADMIN USER',
            'username' => 'sub_admin_user',
            'phone' => '+91 9999000002',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_stock', 'view_stock_manager_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(200);
        $response->assertSee('SUB ADMIN USER');
        $response->assertSee('SUB ADMIN');
    }

    public function test_stock_manager_header_displays_name_and_stock_manager_badge()
    {
        $sm = User::create([
            'name' => 'STOCK SUPERVISOR',
            'username' => 'stock_supervisor',
            'phone' => '+91 9999000003',
            'password' => Hash::make('password123'),
            'role' => 'STOCK_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withSession(['auth_user' => $sm->toArray()])
            ->get('/stock_manager/stock');

        $response->assertStatus(200);
        $response->assertSee('STOCK SUPERVISOR');
        $response->assertSee('STOCK MANAGER');
    }

    public function test_cashier_header_displays_name_and_cashier_badge()
    {
        $cashier = User::create([
            'name' => 'HUZEFA LEHRIWALA',
            'username' => 'huzefa',
            'phone' => '+91 9999000004',
            'password' => Hash::make('password123'),
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withSession(['auth_user' => $cashier->toArray()])
            ->get('/cashier/ledger');

        $response->assertStatus(200);
        $response->assertSee('HUZEFA LEHRIWALA');
        $response->assertSee('CASHIER');
    }
}
