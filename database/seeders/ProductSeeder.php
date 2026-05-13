<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 25 RAW MATERIALS (exactly from mockData.js) ─────────────────────
        $rawMaterials = [
            ['name' => 'Fresh Garlic',            'image_url' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Onion',             'image_url' => 'https://images.unsplash.com/photo-1618512496248-a07fe83aa8cb?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Tomato',            'image_url' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Ginger',            'image_url' => 'https://images.unsplash.com/photo-1596368708356-6e1ea8f8cece?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Beetroot',          'image_url' => 'https://images.unsplash.com/photo-1593105544559-ecb03bf76f82?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Carrot',            'image_url' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Potato',            'image_url' => 'https://images.unsplash.com/photo-1518977676601-b53f82ber540?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Spinach',           'image_url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Mint Leaves',       'image_url' => 'https://images.unsplash.com/photo-1628556270448-4d4e4148e1b1?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Coriander Leaves',  'image_url' => 'https://images.unsplash.com/photo-1592928302636-c83cf1e1c887?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Curry Leaves',      'image_url' => 'https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Green Chili',       'image_url' => 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Cabbage',           'image_url' => 'https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Amla',              'image_url' => 'https://images.unsplash.com/photo-1585059895524-72f80dc7e03c?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Mango',             'image_url' => 'https://images.unsplash.com/photo-1553279768-865429fa0078?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Banana',            'image_url' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Papaya',            'image_url' => 'https://images.unsplash.com/photo-1517282009859-f000ec3b26fe?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Guava',             'image_url' => 'https://images.unsplash.com/photo-1536511132770-e5058c7e8c46?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Apple',             'image_url' => 'https://images.unsplash.com/photo-1568702846914-96b305d2ead1?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Pineapple',         'image_url' => 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Orange',            'image_url' => 'https://images.unsplash.com/photo-1547514701-42fee3e1c750?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Pomegranate',       'image_url' => 'https://images.unsplash.com/photo-1541159067299-80c0e8bac5e0?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Jamun',             'image_url' => 'https://images.unsplash.com/photo-1597714026720-8f74c62310ba?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Chickoo',           'image_url' => 'https://images.unsplash.com/photo-1605027990121-cbae9e0642df?w=200&h=200&fit=crop'],
            ['name' => 'Fresh Custard Apple',     'image_url' => 'https://images.unsplash.com/photo-1634315510935-e8f52c677891?w=200&h=200&fit=crop'],
        ];

        foreach ($rawMaterials as $rm) {
            Product::create([
                'name'      => $rm['name'],
                'type'      => 'RAW',
                'unit'      => 'kg',
                'image_url' => $rm['image_url'],
            ]);
        }

        // ─── 38 SEMI-FINISHED PRODUCTS ───────────────────────────────────────
        $semiProducts = [
            'Tomato Powder', 'Lemon Powder', 'Tamarind Powder', 'Beetroot Powder',
            'Green Chili Powder', 'Capsicum Powder', 'Cheese Powder / Cheddar Cheese',
            'Mango Powder', 'Fig Powder', 'Pineapple Powder', 'Papaya Powder',
            'Soya Sauce Powder', 'HVP Powder Groundnut Base', 'HVP Powder Soya Base',
            'Onion Flakes', 'Onion Powder', 'Garlic Flakes', 'Garlic Powder',
            'Dried Mango (Amchur) Powder', 'Potato Flakes', 'Potato Powder',
            'Ginger Powder', 'Spinach Powder', 'Masala Tea', 'Garam Masala',
            'Cumin Powder', 'Turmeric Powder', 'Red Chili Powder', 'Coriander Powder',
            'Black Pepper Powder', 'Coriander Cumin Powder', 'Red Chili Flakes (Pizza Cut)',
            'Magic Masala', 'Chatpata Masala', 'Peri Peri Masala', 'Schezwan Masala',
            'Masala Masti', 'Garlic in Brine'
        ];

        foreach ($semiProducts as $name) {
            Product::create([
                'name' => $name,
                'type' => 'SEMI',
                'unit' => 'kg',
            ]);
        }

        // ─── FINISHED PRODUCTS ───────────────────────────────────────────────
        $finishedProducts = [
            'Packaged Tomato Powder',
            'Packaged Lemon Powder',
            'Packaged Tamarind Powder',
            'Premium Masala Pack (500g)',
            'Curry Base Mix (250g)',
            'Ready-to-use Garlic Paste Box'
        ];

        foreach ($finishedProducts as $name) {
            Product::create([
                'name' => $name,
                'type' => 'FINISHED',
                'unit' => 'kg',
            ]);
        }
    }
}
