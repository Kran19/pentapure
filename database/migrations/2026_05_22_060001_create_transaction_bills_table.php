<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->string('file_path');           // relative path in storage
            $table->string('file_type');           // 'image' or 'pdf'
            $table->string('original_name');       // original filename
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->unsignedInteger('sort_order')->default(0);   // order within transaction
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_bills');
    }
};
