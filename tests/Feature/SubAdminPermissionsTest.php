<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_admin_with_stock_manager_stock_can_access_stock_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Stock Sub Admin',
            'username' => 'stock_subadmin',
            'phone' => '+91 9898000010',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(200);
        $response->assertSee('Live Stock');
    }

    public function test_sub_admin_with_admin_stock_can_access_stock_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Admin Stock Sub Admin',
            'username' => 'admin_stock_subadmin',
            'phone' => '+91 9898000011',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(200);
        $response->assertSee('Live Stock');
    }

    public function test_sub_admin_with_stock_manager_action_can_access_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Action Sub Admin',
            'username' => 'action_subadmin',
            'phone' => '+91 9898000012',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_action']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock-manager/action');

        $response->assertStatus(200);
        $response->assertSee('Stock Inward / Action');
    }

    public function test_sub_admin_with_cashier_ledger_can_access_and_see_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'Cashier Sub Admin',
            'username' => 'cashier_subadmin',
            'phone' => '+91 9898000013',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_cashier_ledger']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/cashier/ledger');

        $response->assertStatus(200);
        $response->assertSee('Cashier Ledger');
    }

    public function test_sub_admin_without_stock_permission_cannot_access_stock_and_no_stock_in_sidebar()
    {
        $subAdmin = User::create([
            'name' => 'No Stock Sub Admin',
            'username' => 'nostock_subadmin',
            'phone' => '+91 9898000014',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_cashier_ledger']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/stock');

        $response->assertStatus(403);
    }
}
