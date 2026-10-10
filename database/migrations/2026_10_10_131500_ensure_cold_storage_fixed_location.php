<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasCold = DB::table('locations')->whereRaw('UPPER(TRIM(name)) = ?', ['COLD STORAGE'])->exists();
        if (!$hasCold) {
            DB::table('locations')->insert([
                'name' => 'Cold Storage',
                'description' => 'Temperature-controlled cold storage warehouse',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No-op to preserve fixed location
    }
};
