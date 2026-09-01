<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN','RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','SUB_ADMIN','STOCK_MANAGER') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_table_for_stock_manager', function (Blueprint $table) {
            //
        });
    }
};
