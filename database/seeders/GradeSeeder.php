<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            'PPF', 'TPR', 'TPS', 'GOLD', 'PREMIUM', 'RICH', 'RICH+', 'EXTRA STRONG', 'REGULAR', 'DELUXE', 'PURE'
        ];

        foreach ($grades as $g) {
            Grade::create(['name' => $g]);
        }
    }
}
