<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default locations
        DB::table('locations')->insert([
            ['name' => 'Warehouse A', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse B', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rack 1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cold Room', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
