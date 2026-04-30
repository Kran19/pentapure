<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cashier ledger
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['IN', 'OUT']);
            $table->decimal('amount', 14, 2);
            $table->string('category')->default('general');
            $table->text('note')->nullable();
            $table->string('reference')->nullable(); // bill no, invoice no
            $table->timestamp('date')->useCurrent();
            $table->timestamps();
        });

        // Raw Material Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who requested
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // raw material
            $table->decimal('quantity', 12, 3);
            $table->text('note')->nullable();
            $table->enum('status', ['PENDING', 'DONE'])->default('PENDING');
            $table->timestamp('date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('transactions');
    }
};
