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
            'Tomato Powder' => ['PPF', 'TPR', 'TPS', 'GOLD', 'PREMIUM', 'RICH', 'RICH+'],
            'Lemon Powder'  => ['PPF', 'GOLD', 'PREMIUM', 'EXTRA STRONG', 'RICH'],
            'Tamarind Powder'=> ['REGULAR', 'PREMIUM', 'EXTRA STRONG'],
            'Beetroot Powder'=> ['REGULAR', 'PREMIUM'],
            'Green Chili Powder' => ['REGULAR', 'PREMIUM', 'EXTRA STRONG'],
            'Capsicum Powder' => ['REGULAR', 'PPF', 'DELUXE', 'PURE'],
            'Cheese Powder / Cheddar Cheese' => ['GOLD', 'PREMIUM'],
            'Mango Powder' => ['PPF', 'TPR', 'TPS', 'GOLD', 'PREMIUM', 'RICH', 'RICH+'],
            'Fig Powder' => ['PPF', 'GOLD', 'PREMIUM', 'RICH'],
            'Pineapple Powder' => ['PPF', 'GOLD', 'PREMIUM', 'RICH'],
            'Papaya Powder' => ['PPF', 'GOLD', 'PREMIUM', 'RICH'],
            'Soya Sauce Powder' => ['REGULAR', 'PREMIUM'],
            'HVP Powder Groundnut Base' => ['REGULAR', 'PREMIUM', 'EXTRA STRONG'],
            'HVP Powder Soya Base' => ['REGULAR', 'PREMIUM', 'EXTRA STRONG'],
            'Onion Flakes' => ['GOLD', 'PREMIUM', 'REGULAR'],
            'Onion Powder' => ['GOLD', 'PREMIUM', 'REGULAR'],
            'Garlic Flakes' => ['GOLD', 'PREMIUM', 'REGULAR'],
            'Garlic Powder' => ['GOLD', 'PREMIUM', 'REGULAR'],
            'Dried Mango (Amchur) Powder' => ['REGULAR', 'PREMIUM', 'EXTRA STRONG'],
            'Potato Flakes' => ['GOLD', 'PREMIUM'],
            'Potato Powder' => ['GOLD', 'PREMIUM'],
            'Ginger Powder' => ['PPF', 'PREMIUM', 'PURE'],
            'Spinach Powder' => ['PPF', 'PREMIUM', 'PURE'],
            'Masala Tea' => ['PREMIUM', 'PURE'],
            'Garam Masala' => ['GOLD', 'PREMIUM', 'PURE'],
            'Cumin Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Turmeric Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Red Chili Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Coriander Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Black Pepper Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Coriander Cumin Powder' => ['GOLD', 'PREMIUM', 'PURE'],
            'Red Chili Flakes (Pizza Cut)' => ['GOLD', 'PREMIUM', 'PURE'],
            'Magic Masala' => ['GOLD', 'PREMIUM', 'PURE'],
            'Chatpata Masala' => ['GOLD', 'PREMIUM', 'PURE'],
            'Peri Peri Masala' => ['GOLD', 'PREMIUM', 'PURE'],
            'Schezwan Masala' => ['GOLD', 'PREMIUM', 'PURE'],
            'Masala Masti' => ['GOLD', 'PREMIUM', 'PURE'],
            'Garlic in Brine' => ['GOLD', 'PREMIUM', 'PURE'],
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
