<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $rawUser = User::where('role', 'RAW')->first();
        $semiUser = User::where('role', 'SEMI')->first();
        $finishedUser = User::where('role', 'FINISHED')->first();

        $location = \App\Models\Location::firstOrCreate(['name' => 'Main Warehouse']);

        // 1. Seed some Raw Stock
        $rawMaterial = Product::where('name', 'Raw Material A')->first();
        if ($rawMaterial) {
            Stock::create([
                'product_id' => $rawMaterial->id,
                'user_id'    => $rawUser->id,
                'stage'      => 'RAW',
                'grade'      => 'NONE',
                'location_id'=> $location->id,
                'quantity'   => 500,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }

        // 2. Seed some Semi Stock with GRADES
        $processedProduct = Product::where('name', 'Product A (Processed)')->first();
        if ($processedProduct && $semiUser) {
            // Semi in Grade A
            Stock::create([
                'product_id' => $processedProduct->id,
                'user_id'    => $semiUser->id,
                'stage'      => 'SEMI',
                'grade'      => 'Grade A',
                'location_id'=> $location->id,
                'quantity'   => 200,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
            // Semi in Grade B
            Stock::create([
                'product_id' => $processedProduct->id,
                'user_id'    => $semiUser->id,
                'stage'      => 'SEMI',
                'grade'      => 'Grade B',
                'location_id'=> $location->id,
                'quantity'   => 50,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }

        // 3. Seed some Finished Stock
        if ($processedProduct && $finishedUser) {
            Stock::create([
                'product_id' => $processedProduct->id,
                'user_id'    => $finishedUser->id,
                'stage'      => 'FINISHED',
                'grade'      => 'Grade A',
                'location_id'=> $location->id,
                'quantity'   => 100,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }
    }
}
