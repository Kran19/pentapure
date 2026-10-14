<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to permanently clear test attendance data.
     * Keeps workers, departments, users, and stock intact.
     */
    public function up(): void
    {
        try {
            Schema::disableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (\Throwable $e) {}

        $tables = [
            'attendances',
            'attendance_submissions',
            'worker_monthly_adjustments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::table($table)->truncate();
                } catch (\Throwable $e) {
                    DB::table($table)->delete();
                }
            }
        }

        try {
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: cleared test data cannot and should not be restored
    }
};
