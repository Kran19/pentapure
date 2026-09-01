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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('shift_type')->nullable();
            $table->string('ot_ut')->default('NONE'); // NONE, OT, UT
            $table->decimal('ot_ut_hours', 5, 2)->default(0);
            $table->decimal('advance', 10, 2)->default(0);
            $table->boolean('is_finished')->default(false);
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE attendances MODIFY COLUMN status VARCHAR(255) DEFAULT 'ABSENT'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['shift_type', 'ot_ut', 'ot_ut_hours', 'advance', 'is_finished']);
        });
    }
};
