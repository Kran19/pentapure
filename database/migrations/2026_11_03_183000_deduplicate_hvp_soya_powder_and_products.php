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
            $limits = DB::table('stock_limits')->get();
            $seen = [];
            $idsToDelete = [];

            foreach ($limits as $l) {
                $st = strtoupper(trim((string)$l->stage));
                if (in_array($st, ['FG', 'FINISHED'])) $st = 'FINISHED';
                elseif (in_array($st, ['PKG', 'PM', 'PACKAGING'])) $st = 'PACKAGING';
                elseif ($st === 'SEMI') $st = 'SEMI';
                elseif ($st === 'RAW') $st = 'RAW';

                $gr = strtoupper(trim((string)($l->grade ?? '')));
                if (in_array($gr, ['', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-'])) $gr = 'NONE';

                $key = "{$l->product_id}_{$st}_{$gr}";
                if (isset($seen[$key])) {
                    $idsToDelete[] = $l->id;
                } else {
                    $seen[$key] = $l->id;
                }
            }

            if (!empty($idsToDelete)) {
                DB::table('stock_limits')->whereIn('id', $idsToDelete)->delete();
            }

            // 2. Find and merge duplicate products in products table (e.g. HVP SOYA POWDER SD)
            $products = Product::orderBy('id', 'asc')->get();
            $grouped = [];
            foreach ($products as $p) {
                $nameNorm = strtoupper(trim((string)$p->name));
                $tNorm = strtoupper(trim((string)$p->type));
                if (in_array($tNorm, ['FG', 'FINISHED'])) $tNorm = 'FINISHED';
                elseif (in_array($tNorm, ['PKG', 'PM', 'PACKAGING'])) $tNorm = 'PACKAGING';

                $key = "{$nameNorm}_{$tNorm}";
                $grouped[$key][] = $p;
            }

            foreach ($grouped as $prods) {
                if (count($prods) <= 1) continue;

                $primary = $prods[0];
                $duplicatesToDelete = array_slice($prods, 1);

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

