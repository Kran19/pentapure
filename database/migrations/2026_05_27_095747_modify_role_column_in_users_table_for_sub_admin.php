<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN','RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','SUB_ADMIN') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_table_for_sub_admin', function (Blueprint $table) {
            //
        });
    }
};
