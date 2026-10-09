<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Location;

return new class extends Migration
{
    public function up(): void
    {
        $coldStorage = Location::whereRaw('LOWER(TRIM(name)) = ?', ['cold storage'])->first();
        if (!$coldStorage) {
            $candidate = Location::find(4);
            if ($candidate && !in_array(strtoupper(trim($candidate->name)), ['MAIN WAREHOUSE', 'DEFAULT'], true)) {
                if (str_contains(strtolower($candidate->name), 'cold')) {
                    $coldStorage = $candidate;
                }
            }
        }

        if ($coldStorage) {
            $fallback = Location::where('id', '!=', $coldStorage->id)
                ->where(function ($q) {
                    $q->whereRaw('UPPER(TRIM(name)) = ?', ['MAIN WAREHOUSE'])
                      ->orWhereRaw('UPPER(TRIM(name)) = ?', ['DEFAULT']);
                })->first() ?? Location::where('id', '!=', $coldStorage->id)->first();

            if ($fallback) {
                DB::table('stocks')->where('location_id', $coldStorage->id)->update(['location_id' => $fallback->id]);
                DB::table('dispatch_item_locations')->where('location_id', $coldStorage->id)->update(['location_id' => $fallback->id]);
            }

            $coldStorage->delete();
        }
    }

    public function down(): void
    {
        // No-op
    }
};
