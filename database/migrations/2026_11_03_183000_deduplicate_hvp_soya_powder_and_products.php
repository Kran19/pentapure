<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // 1. Clean up duplicate stock_limits where stage & grade normalize to the same values
            $stageLimitNorm = "CASE 
                WHEN UPPER(TRIM(stage)) IN ('FG', 'FINISHED') THEN 'FINISHED' 
                WHEN UPPER(TRIM(stage)) IN ('PKG', 'PM', 'PACKAGING') THEN 'PACKAGING' 
                WHEN UPPER(TRIM(stage)) = 'SEMI' THEN 'SEMI' 
                WHEN UPPER(TRIM(stage)) = 'RAW' THEN 'RAW' 
                ELSE UPPER(TRIM(stage)) 
            END";
            $gradeLimitNorm = "CASE 
                WHEN grade IS NULL OR UPPER(TRIM(grade)) IN ('', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-') THEN 'NONE' 
                ELSE UPPER(TRIM(grade)) 
            END";

            $dupLimits = DB::table('stock_limits')
                ->selectRaw("product_id, {$stageLimitNorm} as norm_stage, {$gradeLimitNorm} as norm_grade, COUNT(*) as cnt, MAX(id) as max_id")
                ->groupBy('product_id', DB::raw("{$stageLimitNorm}"), DB::raw("{$gradeLimitNorm}"))
                ->havingRaw("COUNT(*) > 1")
                ->get();

            foreach ($dupLimits as $dl) {
                DB::table('stock_limits')
                    ->where('product_id', $dl->product_id)
                    ->whereRaw("{$stageLimitNorm} = ?", [$dl->norm_stage])
                    ->whereRaw("{$gradeLimitNorm} = ?", [$dl->norm_grade])
                    ->where('id', '!=', $dl->max_id)
                    ->delete();
            }

            // 2. Find and merge duplicate products in products table (e.g. HVP SOYA POWDER SD)
            $typeNormSql = "CASE 
                WHEN UPPER(TRIM(type)) IN ('FG', 'FINISHED') THEN 'FINISHED' 
                WHEN UPPER(TRIM(type)) IN ('PKG', 'PM', 'PACKAGING') THEN 'PACKAGING' 
                WHEN UPPER(TRIM(type)) = 'SEMI' THEN 'SEMI' 
                WHEN UPPER(TRIM(type)) = 'RAW' THEN 'RAW' 
                ELSE UPPER(TRIM(type)) 
            END";

            $duplicates = DB::table('products')
                ->selectRaw("UPPER(TRIM(name)) as norm_name, {$typeNormSql} as norm_type, COUNT(*) as cnt")
                ->groupBy(DB::raw("UPPER(TRIM(name))"), DB::raw("{$typeNormSql}"))
                ->havingRaw("COUNT(*) > 1")
                ->get();

            foreach ($duplicates as $dup) {
                $products = Product::whereRaw("UPPER(TRIM(name)) = ?", [$dup->norm_name])
                    ->whereRaw("{$typeNormSql} = ?", [$dup->norm_type])
                    ->orderBy('id', 'asc')
                    ->get();

                if ($products->count() <= 1) {
                    continue;
                }

                $primary = $products->first();
                $duplicatesToDelete = $products->slice(1);

                $primaryStock = (float) DB::table('stocks')
                    ->where('product_id', $primary->id)
                    ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
                    ->value('net');

                foreach ($duplicatesToDelete as $extra) {
                    $extraStock = (float) DB::table('stocks')
                        ->where('product_id', $extra->id)
                        ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
                        ->value('net');

                    // If primary had no stock, but extra has stock, reassign extra's stock to primary
                    if ($primaryStock <= 0 && $extraStock > 0) {
                        DB::table('stocks')->where('product_id', $extra->id)->update(['product_id' => $primary->id]);
                        $primaryStock = $extraStock;
                    } else {
                        // Clean up duplicate stock on the extra product
                        DB::table('stocks')->where('product_id', $extra->id)->delete();
                    }

                    // Reassign any orders / order_items from extra to primary
                    DB::table('order_items')->where('product_id', $extra->id)->update(['product_id' => $primary->id]);

                    // Reassign production logs
                    DB::table('production_logs')->where('output_product_id', $extra->id)->update(['output_product_id' => $primary->id]);
                    DB::table('production_logs')->where('input_product_id', $extra->id)->update(['input_product_id' => $primary->id]);

                    // Reassign transactions
                    DB::table('transactions')->where('product_id', $extra->id)->update(['product_id' => $primary->id]);

                    // Delete pivot & limits for extra
                    DB::table('grade_product')->where('product_id', $extra->id)->delete();
                    DB::table('stock_limits')->where('product_id', $extra->id)->delete();

                    // Delete the duplicate product
                    $extra->delete();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('deduplicate migration warning: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for deduplication
    }
};
