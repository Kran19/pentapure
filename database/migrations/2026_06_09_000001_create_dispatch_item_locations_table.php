<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_item_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispatch_log_item_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 10, 3);
            $table->unsignedBigInteger('stock_id')->nullable(); // References stock transaction
            $table->timestamps();

            // Relationships
            $table->foreign('dispatch_log_item_id')
                ->references('id')
                ->on('dispatch_log_items')
                ->onDelete('cascade');
            
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('restrict');
            
            $table->foreign('stock_id')
                ->references('id')
                ->on('stocks')
                ->onDelete('set null');

            // Indexes
            $table->index('dispatch_log_item_id');
            $table->index('location_id');
            $table->index('stock_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_item_locations');
    }
};
