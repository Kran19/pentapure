<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One production run = one log
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['SEMI', 'FINISHED']); // which stage produced
            $table->foreignId('output_product_id')->constrained('products')->onDelete('cascade');
            $table->string('output_grade')->default('NONE');
            $table->decimal('output_qty', 12, 3);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();
        });

        // Raw inputs consumed in that one production run
        Schema::create('production_log_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('input_product_id')->constrained('products')->onDelete('cascade');
            $table->string('input_grade')->default('NONE');
            $table->decimal('quantity', 12, 3); // amount consumed (always positive, deducted from raw stock)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_log_inputs');
        Schema::dropIfExists('production_logs');
    }
};
