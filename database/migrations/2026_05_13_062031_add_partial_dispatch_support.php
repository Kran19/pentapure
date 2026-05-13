<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track how much of each order item has been dispatched so far
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('dispatched_qty', 12, 3)->default(0)->after('quantity');
        });

        // Store per-item quantities for each dispatch round
        Schema::create('dispatch_log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_log_id')->constrained('dispatch_logs')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_log_items');
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('dispatched_qty');
        });
    }
};
