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

        // Define the grades as per user request
        $gradeNames = [
            'A GRADE', 'PURE', 'REGULAR', 'GOLD', 'PPF', 'PREMIUM', 'SORTED', 
            'UNSORTED', 'EXTRASTRONG', 'RICHPLUS', 'RICH', 'AB 30', 'TPA', 'TPM', 
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

            // Clean up the name: remove (FG), (RM), (SM), or (RAW), (SEMI)
            // Example: "CHEESE POWDER SD PREMIUM (FG)" -> "CHEESE POWDER SD PREMIUM"
            $cleanName = preg_replace('/\s*\((FG|RM|SM|RAW|SEMI)\)$/i', '', $rawName);
            $cleanName = trim($cleanName);

            // Extract grade from the end of the name
            $foundGrade = null;
            // Sort grade names by length descending to match 'RICHPLUS' before 'RICH'
            $sortedGrades = $gradeNames;
            usort($sortedGrades, function($a, $b) { return strlen($b) - strlen($a); });

            foreach ($sortedGrades as $g) {
                $gNoSpace = str_replace(' ', '', $g);
                if (preg_match('/' . preg_quote($gNoSpace, '/') . '$/i', str_replace(' ', '', $cleanName))) {
                    $foundGrade = $g;
                    // Strip the grade from the name allowing arbitrary spaces
                    $regexGrade = preg_replace('/(.)/i', '$1\s*', $gNoSpace);
                    $cleanName = preg_replace('/' . $regexGrade . '$/i', '', $cleanName);
                    break;
                }
            }

            $name = trim($cleanName);

            $product = Product::updateOrCreate(
                ['name' => $name, 'type' => $dbType], // unique by name and type
                [
                    'unit' => 'KG', // default unit
                    'rate' => 0,
                    'threshold' => 0,
                    'is_active' => true,
                ]
            );

            // Attach grade if found
            if ($foundGrade && isset($gradeMap[$foundGrade])) {
                $product->grades()->syncWithoutDetaching([$gradeMap[$foundGrade]]);
            }

            $count++;
        }

        $this->command->info("Successfully processed and imported {$count} products!");
    }
}
