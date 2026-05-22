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
        Schema::create('stock_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('stage');
            $table->string('grade')->default('NONE');
            $table->decimal('alert_limit', 10, 2)->default(0);
            $table->timestamps();
            
            // Ensure only one limit per product+stage+grade
            $table->unique(['product_id', 'stage', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_limits');
    }
};
