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
            $this->command->info("Please paste the raw text of products into this file and run the seeder again.");
            return;
        }

        $content = file_get_contents($filePath);
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($lines)) {
            $this->command->error("The file is empty.");
            return;
        }

        // Truncate existing data to prevent duplicates
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('grade_product')->truncate();
        \Illuminate\Support\Facades\DB::table('grades')->truncate();
        \Illuminate\Support\Facades\DB::table('products')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Define the grades as per user request
        $gradeNames = [
            'A GRADE', 'PURE', 'REGULAR', 'GOLD', 'PPF', 'PREMIUM', 'SORTED', 
            'UNSORTED', 'EXTRASTRONG', 'EXTRA STRONG', 'RICHPLUS', 'RICH PLUS', 'RICH', 'AB 30', 'TPA', 'TPM', 
            'TPR', 'TPS', 'UNIQUE', 'NONE'
        ];

        // Ensure all grades exist in DB
        $gradeMap = [];
        foreach ($gradeNames as $gName) {
            $g = \App\Models\Grade::firstOrCreate(
                ['name' => $gName],
                ['is_active' => true]
            );
            $gradeMap[$gName] = $g->id;
        }

        $count = 0;
        foreach ($lines as $line) {
            $rawName = trim($line);
            
            // Map type code to DB enum based on tag
            $dbType = 'RAW'; // default
            if (preg_match('/\((FG)\)$/i', $rawName)) {
                $dbType = 'FINISHED';
            } elseif (preg_match('/\((SEMI|SM)\)$/i', $rawName)) {
                $dbType = 'SEMI';
            } elseif (preg_match('/\((RAW|RM)\)$/i', $rawName)) {
                $dbType = 'RAW';
            }

            // DO NOT CLEAN NAME. The user wants the exact name from the file!
            $name = $rawName;

            // Extract grade from the string (checking if the grade exists in the string)
            $foundGrade = null;
            // Sort grade names by length descending to match 'RICH PLUS' before 'RICH'
            $sortedGrades = $gradeNames;
            usort($sortedGrades, function($a, $b) { return strlen($b) - strlen($a); });

            // Look for a hyphen and the grade after it, or just the grade before the parenthesis
            foreach ($sortedGrades as $g) {
                // Remove spaces for comparison just in case
                $gNoSpace = str_replace(' ', '', $g);
                $nameNoSpace = str_replace(' ', '', $rawName);
                
                // If the user explicitly added a hyphen like `- UNIQUE` or `-UNIQUE`
                if (preg_match('/-' . preg_quote($gNoSpace, '/') . '/i', $nameNoSpace)) {
                    $foundGrade = $g;
                    break;
                }
                
                // Or if the grade is just in the string (before the parenthesis)
                if (preg_match('/' . preg_quote($gNoSpace, '/') . '\(+/i', $nameNoSpace)) {
                    $foundGrade = $g;
                    break;
                }
            }

            // Create product with the full exact name
            $product = Product::create([
                'name' => $name,
                'type' => $dbType,
                'unit' => 'KG', // default unit
                'rate' => 0,
                'threshold' => 0,
                'is_active' => true,
            ]);

            // Attach grade if found
            if ($foundGrade && isset($gradeMap[$foundGrade])) {
                $product->grades()->attach($gradeMap[$foundGrade]);
            }

            $count++;
        }

        $this->command->info("Successfully processed and imported {$count} distinct products!");
    }
}
