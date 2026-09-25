<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin - sets passwords for everyone
        $admin = User::updateOrCreate(
            ['email' => 'admin@pentapure.com'],
            [
                'name'       => 'Super Admin',
                'username'   => 'admin',
                'password'   => Hash::make('admin@123'),
                'role'       => 'ADMIN',
                'parent_id'  => null,
                'status'     => 'ACTIVE',
            ]
        );

        // Operational users - admin sets their passwords
        $roles = [
            ['name' => 'Amit',          'username' => 'raw',          'email' => 'raw@pentapure.com',        'role' => 'RAW',           'password' => 'raw@123'],
            ['name' => 'Rahul',         'username' => 'semi',         'email' => 'semi@pentapure.com',       'role' => 'SEMI',          'password' => 'semi@123'],
            ['name' => 'Vikram',        'username' => 'finished',     'email' => 'finished@pentapure.com',   'role' => 'FINISHED',      'password' => 'finished@123'],
            ['name' => 'Sneha',         'username' => 'cashier',      'email' => 'cashier@pentapure.com',    'role' => 'CASHIER',       'password' => 'cashier@123'],
            ['name' => 'Raj',           'username' => 'sales',        'email' => 'sales@pentapure.com',      'role' => 'SALES',         'password' => 'sales@123'],
            ['name' => 'Ravi',          'username' => 'dispatch',     'email' => 'dispatch@pentapure.com',   'role' => 'DISPATCH',      'password' => 'dispatch@123'],
            ['name' => 'Manager',       'username' => 'attendance',   'email' => 'attendance@pentapure.com', 'role' => 'ATTENDANCE',    'password' => 'attendance@123'],
            ['name' => 'Stock Manager', 'username' => 'stockmanager', 'email' => 'stockmanager@pentapure.com', 'role' => 'STOCK_MANAGER', 'password' => 'stock@123', 'permissions' => ['can_manage', 'view_stock_manager_home', 'edit_stock_manager_home', 'stock_manager_home', 'view_stock_manager_action', 'edit_stock_manager_action', 'stock_manager_action', 'view_stock_manager_stock', 'edit_stock_manager_stock', 'stock_manager_stock', 'view_stock_manager_po', 'edit_stock_manager_po', 'stock_manager_po', 'view_stock_manager_history', 'edit_stock_manager_history', 'stock_manager_history']],
        ];

        foreach ($roles as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'        => $data['name'],
                    'username'    => $data['username'],
                    'password'    => Hash::make($data['password']),
                    'role'        => $data['role'],
                    'parent_id'   => $admin->id,
                    'status'      => 'ACTIVE',
                    'permissions' => $data['permissions'] ?? [],
                ]
            );
        }
    }
}
