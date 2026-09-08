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
            $table->boolean('is_paid')->default(false)->after('remark');
            $table->text('paid_note')->nullable()->after('is_paid');
            $table->timestamp('paid_at')->nullable()->after('paid_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_monthly_adjustments', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'paid_note', 'paid_at']);
        });
    }
};
