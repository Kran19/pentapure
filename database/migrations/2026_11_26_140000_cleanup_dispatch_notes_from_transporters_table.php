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
                ->orWhere('vehicles', 'LIKE', '%DISPATCH%')
                ->orWhere('vehicles', 'LIKE', '%DUE TO%')
                ->orWhere('vehicles', 'LIKE', '%APP.PROBLEM%')
                ->orWhere('vehicles', 'LIKE', '%APP%')
                ->orWhere('vehicles', 'LIKE', '%PROBLEM%')
                ->orWhere('vehicles', 'LIKE', '%DELAY%')
                ->orWhere('vehicles', 'LIKE', '%PENDING%')
                ->orWhere('vehicles', 'LIKE', '%REASON%')
                ->orWhere('vehicles', 'LIKE', '%REMARK%')
                ->orWhere('vehicles', 'LIKE', '%NOTE%')
                ->orWhereRaw('LENGTH(vehicles) > 35')
                ->update(['vehicles' => null]);

            DB::table('transporters')
                ->where('name', 'LIKE', '%JAI TRANSPORT%')
                ->where(function ($q) {
                    $q->where('vehicles', 'LIKE', '%ACTUAL DISPATCH%')
                      ->orWhere('vehicles', 'LIKE', '%DISPATCH%')
                      ->orWhere('vehicles', 'LIKE', '%PROBLEM%');
                })
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
