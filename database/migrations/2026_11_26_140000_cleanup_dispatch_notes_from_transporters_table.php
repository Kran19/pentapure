<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::table('transporters')
                ->where('vehicles', 'LIKE', '%ACTUAL DISPATCH%')
                ->orWhere('vehicles', 'LIKE', '%DUE TO%')
                ->orWhere('vehicles', 'LIKE', '%APP.PROBLEM%')
                ->orWhere('vehicles', 'LIKE', '%PROBLEM%')
                ->update(['vehicles' => null]);
        } catch (\Throwable $e) {
            // Silently continue if table or driver differs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
