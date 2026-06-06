<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Ensures grade availability is consistent with business rules:
 * - RAW products: no grade selection (grades are not attached)
 * - SEMI/FINISHED products: grades are attached as per ProductGradeSeeder
 *
 * This prevents UI/logic issues where a user picks a product but sees "Not Available"
 * because the chosen grade doesn't exist for that product.
 */
class DispatchGradeSeeder extends Seeder
{
    public function run(): void
    {
        // Attach grades only for non-RAW products.
        // We rely on ProductGradeSeeder to attach the correct grade matrix for manufactured products.

        $rawProducts = Product::where('type', 'RAW')->get();
        foreach ($rawProducts as $p) {
            // Detach any existing grades to guarantee RAW has no grade options.
            if (method_exists($p, 'grades')) {
                $p->grades()->sync([]);
            }
        }

        // Guarantee grades referenced in pivot exist (defensive)
        $allGrades = [
            'PPF','TPR','TPS','GOLD','PREMIUM','RICH','RICH+','EXTRA STRONG','REGULAR','DELUXE','PURE'
        ];
        foreach ($allGrades as $name) {
            Grade::firstOrCreate(['name' => $name]);
        }
    }
}

