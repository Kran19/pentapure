<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        $stockPerms = [
            'can_manage',
            'view_stock_manager_home', 'edit_stock_manager_home', 'stock_manager_home',
            'view_stock_manager_action', 'edit_stock_manager_action', 'stock_manager_action',
            'view_stock_manager_stock', 'edit_stock_manager_stock', 'stock_manager_stock',
            'view_stock_manager_po', 'edit_stock_manager_po', 'stock_manager_po',
            'view_stock_manager_history', 'edit_stock_manager_history', 'stock_manager_history'
        ];

        $stockUsers = User::where('role', 'STOCK_MANAGER')->get();
        foreach ($stockUsers as $su) {
            $existing = is_array($su->permissions) ? $su->permissions : [];
            $su->permissions = array_values(array_unique(array_merge($existing, $stockPerms)));
            $su->save();
        }
    }

    public function down(): void
    {
    }
};
