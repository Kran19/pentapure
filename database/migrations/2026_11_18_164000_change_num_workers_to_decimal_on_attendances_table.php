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
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'num_workers')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('num_workers', 8, 2)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'num_workers')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->integer('num_workers')->nullable()->change();
            });
        }
    }
};
