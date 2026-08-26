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
        Schema::create('worker_monthly_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7); // e.g., '2026-08'
            $table->decimal('petrol_food_amount', 10, 2)->default(0);
            $table->decimal('advance', 10, 2)->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();
            
            $table->unique(['worker_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_monthly_adjustments');
    }
};
