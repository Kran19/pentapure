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
        Schema::table('workers', function (Blueprint $table) {
            $table->enum('salary_type', ['DAILY', 'MONTHLY'])->default('DAILY')->after('shift_type');
            $table->decimal('salary_amount', 10, 2)->default(0)->after('salary_type');
        });

        // Initialize salary_amount from daily_salary for existing records
        DB::table('workers')->update([
            'salary_amount' => DB::raw('daily_salary')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['salary_type', 'salary_amount']);
        });
    }
};
