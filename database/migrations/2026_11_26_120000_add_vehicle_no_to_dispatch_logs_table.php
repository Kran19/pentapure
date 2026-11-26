<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispatch_logs') && !Schema::hasColumn('dispatch_logs', 'vehicle_no')) {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                $table->string('vehicle_no')->nullable()->after('driver_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dispatch_logs') && Schema::hasColumn('dispatch_logs', 'vehicle_no')) {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                $table->dropColumn('vehicle_no');
            });
        }
    }
};
