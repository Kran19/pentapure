<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gst')->nullable();
            $table->text('address')->nullable();
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gst')->nullable();
            $table->string('contact')->nullable();
            $table->string('vehicles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporters');
        Schema::dropIfExists('companies');
    }
};
