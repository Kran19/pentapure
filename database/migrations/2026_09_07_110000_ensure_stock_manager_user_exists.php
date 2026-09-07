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

            User::firstOrCreate(
                ['email' => 'stockmanager@pentapure.com'],
                [
                    'name'        => 'Stock Manager',
                    'password'    => Hash::make('stock@123'),
                    'role'        => 'STOCK_MANAGER',
                    'parent_id'   => $adminId,
                    'status'      => 'ACTIVE',
                    'permissions' => ['can_manage', 'module_dashboard', 'module_stock', 'module_products', 'module_po', 'module_logs', 'module_grades', 'module_locations'],
                ]
            );
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
