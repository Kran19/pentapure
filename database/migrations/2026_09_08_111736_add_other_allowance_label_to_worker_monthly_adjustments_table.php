<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_monthly_adjustments', function (Blueprint $table) {
            $table->string('other_allowance_label')->nullable()->default('PETROL / FOODS');
        });
    }

    public function down(): void
    {
        Schema::table('worker_monthly_adjustments', function (Blueprint $table) {
            $table->dropColumn('other_allowance_label');
        });
    }
};
