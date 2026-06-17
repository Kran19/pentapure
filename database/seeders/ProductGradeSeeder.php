<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductGradeSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'Product A (Processed)' => ['Grade A', 'Grade B', 'Grade C'],
            'Product B (Processed)' => ['Grade A', 'Grade B'],
            'Product C (Processed)' => ['Grade A'],
        ];

        foreach ($matrix as $productName => $gradeNames) {
            $product = Product::where('name', $productName)->first();
            if ($product) {
                // Ensure all grades exist
                foreach ($gradeNames as $gn) {
                    $grade = Grade::firstOrCreate(['name' => $gn]);
                    $product->grades()->syncWithoutDetaching([$grade->id]);
                }
            }
        }
    }
}
