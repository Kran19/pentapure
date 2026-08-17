<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class BulkProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = database_path('seeders/bulk_products.txt');
        
        if (!file_exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($lines)) {
            $this->command->error("The file is empty.");
            return;
        }

        // Truncate existing data to prevent duplicates
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('stocks')->truncate();
        \Illuminate\Support\Facades\DB::table('grade_product')->truncate();
        \Illuminate\Support\Facades\DB::table('products')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $count = 1;
        foreach ($lines as $line) {
            $rawName = trim($line);
            
            // Assign type based on line number
            if ($count >= 1 && $count <= 127) {
                $dbType = 'FINISHED';
            } elseif ($count >= 128 && $count <= 178) {
                $dbType = 'RAW';
            } else {
                $dbType = 'SEMI';
            }

            // Strip the stage tag from the name
            $name = preg_replace('/\s*\((FG|RAW|RM|SEMI|SM)\)$/i', '', $rawName);
            $name = trim($name);

            $gradeName = 'NONE';
            if (strpos($name, ' - ') !== false) {
                $parts = explode(' - ', $name);
                $gradeName = trim(array_pop($parts));
                $name = trim(implode(' - ', $parts));
            }

            // Create or get base product
            $product = Product::firstOrCreate([
                'name' => $name,
                'type' => $dbType,
            ], [
                'unit' => 'KG',
                'rate' => 0,
                'threshold' => 0,
                'is_active' => true,
                'sort_order' => $count,
            ]);

            // Create or get grade
            $grade = \App\Models\Grade::firstOrCreate(['name' => $gradeName]);

            // Attach grade
            $product->grades()->syncWithoutDetaching([$grade->id]);

            $count++;
        }

        $this->command->info("Successfully processed and imported " . ($count - 1) . " products!");
    }
}
