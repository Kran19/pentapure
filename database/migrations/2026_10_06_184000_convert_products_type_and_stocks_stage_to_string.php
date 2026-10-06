<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Convert type from strict ENUM to VARCHAR(50) so PACKAGING and any future categories never fail with 1265 truncation
            DB::statement("ALTER TABLE products MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'RAW'");
            DB::statement("ALTER TABLE stocks MODIFY COLUMN stage VARCHAR(50) NOT NULL DEFAULT 'RAW'");
        }

        // Automatically update Lamination Roll (ID 112 or matching name) from RAW to PACKAGING as requested
        try {
            DB::table('products')
                ->where('id', 112)
                ->orWhere('name', 'LIKE', '%LAMINATION ROLL%')
                ->update(['type' => 'PACKAGING']);

            $packagingProductIds = DB::table('products')
                ->where('type', 'PACKAGING')
                ->pluck('id')
                ->toArray();

            if (!empty($packagingProductIds)) {
                DB::table('stocks')
                    ->whereIn('product_id', $packagingProductIds)
                    ->where('stage', 'RAW')
                    ->update(['stage' => 'PACKAGING']);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lamination Roll packaging migration note: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('RAW', 'SEMI', 'FINISHED', 'PACKAGING') NOT NULL DEFAULT 'RAW'");
            DB::statement("ALTER TABLE stocks MODIFY COLUMN stage ENUM('RAW', 'SEMI', 'FINISHED', 'PACKAGING') NOT NULL DEFAULT 'RAW'");
        }
    }
};
