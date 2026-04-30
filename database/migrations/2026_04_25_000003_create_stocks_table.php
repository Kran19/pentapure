<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unified inventory ledger for all stock types
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who did the action
            $table->enum('stage', ['RAW', 'SEMI', 'FINISHED']); // which stockroom
            $table->string('grade')->default('NONE');
            $table->decimal('quantity', 12, 3); // positive = IN, negative = OUT
            $table->enum('transaction_type', ['IN', 'OUT']);
            $table->timestamp('date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
