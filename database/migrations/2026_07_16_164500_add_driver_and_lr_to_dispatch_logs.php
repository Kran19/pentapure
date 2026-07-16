<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_logs', function (Blueprint $table) {
            $table->string('driver_no')->nullable()->after('transporter_id');
            $table->string('lr_no')->nullable()->after('driver_no');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_logs', function (Blueprint $table) {
            $table->dropColumn(['driver_no', 'lr_no']);
        });
    }
};
