<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            'Grade A', 'Grade B', 'Grade C'
        ];

        foreach ($grades as $g) {
            Grade::firstOrCreate(['name' => $g]);
        }
    }
}
