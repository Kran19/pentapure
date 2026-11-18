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
        if (Schema::hasTable('stocks') && !Schema::hasColumn('stocks', 'recorded_at')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->timestamp('recorded_at')->nullable()->after('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'recorded_at')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('recorded_at');
            });
        }
    }
};
