<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

class FixProductGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:fix-grades';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts grades from product names (after " - ") and assigns them properly, merging duplicate base names.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix product grades...');

        $products = Product::all();
        $processedCount = 0;

        foreach ($products as $p) {
            if (strpos($p->name, ' - ') !== false) {
                // E.g., "CHEESE POWDER SD - PREMIUM" -> base: "CHEESE POWDER SD", grade: "PREMIUM"
                $parts = explode(' - ', $p->name);
                $gradeName = trim(array_pop($parts));
                $baseName = trim(implode(' - ', $parts));

                $this->info("Found: '{$p->name}' -> Base: '{$baseName}', Grade: '{$gradeName}'");

                // Get or create the Grade
                $grade = Grade::firstOrCreate(['name' => $gradeName]);

                // Check if the base product already exists
                $baseProduct = Product::where('name', $baseName)->where('type', $p->type)->first();

                if ($baseProduct && $baseProduct->id !== $p->id) {
                    $this->info("  Merging into existing base product ID: {$baseProduct->id}");
                    // Merge!
                    // Attach the new grade to the base product
                    $baseProduct->grades()->syncWithoutDetaching([$grade->id]);

                    // Update stock entries: reassign them to the base product, and update the grade
                    DB::table('stocks')
                        ->where('product_id', $p->id)
                        ->update([
                            'product_id' => $baseProduct->id,
                            'grade' => $gradeName
                        ]);

                    // Delete the old "name - grade" product
                    $p->delete();
                } else {
                    $this->info("  Renaming current product to base name.");
                    // Just rename it
                    $p->name = $baseName;
                    $p->save();

                    // Attach the grade
                    $p->grades()->syncWithoutDetaching([$grade->id]);
                    
                    // The original seeded product had grade=NONE in stock ledger. Update it.
                    DB::table('stocks')
                        ->where('product_id', $p->id)
                        ->update(['grade' => $gradeName]);
                }
                
                $processedCount++;
            }
        }

        $this->info("Completed. Processed {$processedCount} products.");
    }
}
