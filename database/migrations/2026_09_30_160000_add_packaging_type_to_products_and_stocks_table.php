<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('RAW', 'SEMI', 'FINISHED', 'PACKAGING') NOT NULL");
            DB::statement("ALTER TABLE stocks MODIFY COLUMN stage ENUM('RAW', 'SEMI', 'FINISHED', 'PACKAGING') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('RAW', 'SEMI', 'FINISHED') NOT NULL");
            DB::statement("ALTER TABLE stocks MODIFY COLUMN stage ENUM('RAW', 'SEMI', 'FINISHED') NOT NULL");
        }
    }
};
