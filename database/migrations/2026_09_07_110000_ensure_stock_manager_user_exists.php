<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure enum role column includes STOCK_MANAGER in MySQL
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN','RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','SUB_ADMIN','STOCK_MANAGER') NOT NULL");
            } catch (\Exception $e) {
                // Ignore if already set or error
            }
        }

        // 2. Ensure default Stock Manager user exists in database
        try {
            $admin = User::where('role', 'ADMIN')->first();
            $adminId = $admin ? $admin->id : null;

            $stockPerms = [
                'can_manage',
                'view_stock_manager_home', 'edit_stock_manager_home', 'stock_manager_home',
                'view_stock_manager_action', 'edit_stock_manager_action', 'stock_manager_action',
                'view_stock_manager_stock', 'edit_stock_manager_stock', 'stock_manager_stock',
                'view_stock_manager_po', 'edit_stock_manager_po', 'stock_manager_po',
                'view_stock_manager_history', 'edit_stock_manager_history', 'stock_manager_history'
            ];

            User::updateOrCreate(
                ['email' => 'stockmanager@pentapure.com'],
                [
                    'name'        => 'Stock Manager',
                    'password'    => Hash::make('stock@123'),
                    'role'        => 'STOCK_MANAGER',
                    'parent_id'   => $adminId,
                    'status'      => 'ACTIVE',
                    'permissions' => $stockPerms,
                ]
            );

            // Ensure all STOCK_MANAGER users have full stock manager module permissions
            $stockUsers = User::where('role', 'STOCK_MANAGER')->get();
            foreach ($stockUsers as $su) {
                $existing = is_array($su->permissions) ? $su->permissions : [];
                $su->permissions = array_values(array_unique(array_merge($existing, $stockPerms)));
                $su->save();
            }
        } catch (\Exception $e) {
            // Ignore if error during seed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
