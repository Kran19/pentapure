<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `orders` MODIFY `transporter_id` BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('transporter_id')->nullable()->change();
            });
        }

        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dispatch_logs` MODIFY `transporter_id` BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('transporter_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('transporter_id')->nullable(false)->change();
        });

        Schema::table('dispatch_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('transporter_id')->nullable(false)->change();
        });
    }
};
