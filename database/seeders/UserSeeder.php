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
        $admin = User::create([
            'name'       => 'Super Admin',
            'email'      => 'admin@pentapure.com',
            'password'   => Hash::make('admin@123'),
            'role'       => 'ADMIN',
            'parent_id'  => null,
            'status'     => 'ACTIVE',
        ]);

        // Operational users - admin sets their passwords
        $roles = [
            ['name' => 'Amit',   'email' => 'raw@pentapure.com',      'role' => 'RAW',      'password' => 'raw@123'],
            ['name' => 'Rahul',  'email' => 'semi@pentapure.com',     'role' => 'SEMI',     'password' => 'semi@123'],
            ['name' => 'Vikram', 'email' => 'finished@pentapure.com', 'role' => 'FINISHED', 'password' => 'finished@123'],
            ['name' => 'Sneha',  'email' => 'cashier@pentapure.com',  'role' => 'CASHIER',  'password' => 'cashier@123'],
            ['name' => 'Raj',    'email' => 'sales@pentapure.com',    'role' => 'SALES',    'password' => 'sales@123'],
            ['name' => 'Ravi',   'email' => 'dispatch@pentapure.com', 'role' => 'DISPATCH', 'password' => 'dispatch@123'],
            ['name' => 'Manager','email' => 'attendance@pentapure.com', 'role' => 'ATTENDANCE', 'password' => 'attendance@123'],
        ];

        foreach ($roles as $data) {
            User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'role'      => $data['role'],
                'parent_id' => $admin->id,
                'status'    => 'ACTIVE',
            ]);
        }
    }
}
