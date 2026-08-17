<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('grades')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $grades = [
            'NONE',
            'A GRADE',
            'PURE',
            'REGULAR',
            'GOLD',
            'PPF',
            'PREMIUM',
            'SORTED',
            'UNSORTED',
            'EXTRA STRONG',
            'RICH',
            'RICH PLUS',
            'AB 30',
            'TPA',
            'TPM',
            'TPR',
            'TPS',
            'UNIQUE'
        ];

        foreach ($grades as $g) {
            Grade::firstOrCreate(['name' => $g]);
        }
    }
}
