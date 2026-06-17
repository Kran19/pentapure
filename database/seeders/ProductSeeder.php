<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ─── RAW MATERIALS ─────────────────────
        $rawMaterials = [
            'Raw Material A',
            'Raw Material B',
            'Raw Material C',
        ];

        foreach ($rawMaterials as $name) {
            Product::firstOrCreate(
                ['name' => $name],
                [
                    'type'      => 'RAW',
                    'unit'      => 'kg',
                    'image_url' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=200&h=200&fit=crop',
                ]
            );
        }

        // ─── MANUFACTURED PRODUCTS (Combined Semi & Finished) ─────────────────
        // These can exist in both SEMI and FINISHED stages in stock
        $manufacturedProducts = [
            'Product A (Processed)',
            'Product B (Processed)',
            'Product C (Processed)',
        ];

        foreach ($manufacturedProducts as $name) {
            Product::firstOrCreate(
                ['name' => $name],
                [
                    'type' => 'FINISHED', // Set to FINISHED so Sales can see them all
                    'unit' => 'kg',
                ]
            );
        }
    }
}
