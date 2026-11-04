<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_monthly_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('worker_monthly_adjustments', 'salary_amount')) {
                $table->decimal('salary_amount', 10, 2)->nullable()->after('month');
            }
            if (!Schema::hasColumn('worker_monthly_adjustments', 'salary_type')) {
                $table->string('salary_type', 50)->nullable()->after('salary_amount');
            }
            if (!Schema::hasColumn('worker_monthly_adjustments', 'per_hour_salary')) {
                $table->decimal('per_hour_salary', 10, 2)->nullable()->after('salary_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_monthly_adjustments', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('worker_monthly_adjustments', 'per_hour_salary')) {
                $cols[] = 'per_hour_salary';
            }
            if (Schema::hasColumn('worker_monthly_adjustments', 'salary_type')) {
                $cols[] = 'salary_type';
            }
            if (Schema::hasColumn('worker_monthly_adjustments', 'salary_amount')) {
                $cols[] = 'salary_amount';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
