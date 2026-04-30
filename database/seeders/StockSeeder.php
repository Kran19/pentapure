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

        // 1. Seed some Raw Stock
        $tomatoes = Product::where('name', 'Fresh Tomato')->first();
        if ($tomatoes) {
            Stock::create([
                'product_id' => $tomatoes->id,
                'user_id'    => $rawUser->id,
                'stage'      => 'RAW',
                'grade'      => 'NONE',
                'quantity'   => 500,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }

        // 2. Seed some Semi Stock with GRADES
        $tomatoPowder = Product::where('name', 'Tomato Powder')->first();
        if ($tomatoPowder && $semiUser) {
            // Semi in GOLD grade
            Stock::create([
                'product_id' => $tomatoPowder->id,
                'user_id'    => $semiUser->id,
                'stage'      => 'SEMI',
                'grade'      => 'GOLD',
                'quantity'   => 100,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
            // Semi in PPF grade
            Stock::create([
                'product_id' => $tomatoPowder->id,
                'user_id'    => $semiUser->id,
                'stage'      => 'SEMI',
                'grade'      => 'PPF',
                'quantity'   => 50,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }

        // 3. Seed some Finished Stock
        $pkgTomatoPowder = Product::where('name', 'Packaged Tomato Powder')->first();
        if ($pkgTomatoPowder && $finishedUser) {
            Stock::create([
                'product_id' => $pkgTomatoPowder->id,
                'user_id'    => $finishedUser->id,
                'stage'      => 'FINISHED',
                'grade'      => 'GOLD',
                'quantity'   => 20,
                'transaction_type' => 'IN',
                'date'       => now(),
            ]);
        }
    }
}
