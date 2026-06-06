<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters because of foreign key constraints
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            GradeSeeder::class,
            ProductGradeSeeder::class,
            DispatchGradeSeeder::class,
            StockSeeder::class,

            MonthlyAttendanceSeeder::class,
        ]);
    }
}
