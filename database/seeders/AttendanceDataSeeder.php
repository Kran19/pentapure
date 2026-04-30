<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Worker;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid duplicates
        \Schema::disableForeignKeyConstraints();
        Attendance::truncate();
        Worker::truncate();
        Department::truncate();
        \Schema::enableForeignKeyConstraints();

        // 1. Create Departments
        $depts = [
            'STAFF' => ['BARMAVALA ABBAS BHAI', 'HARIYANI JAVED BHAI', 'BHIL JENTI BHAI', 'BHADURIYA JAGDISH BHAI', 'BORICHA NILESH', 'LAKHANI ONALI', 'LAKHANI SHAKIL BHAI', 'VIRANI HASNAIN', 'LAKHANI MAMAD JAFAR', 'RATHOD BHAVSANG BHAI', 'LAKHANI MINHAL', 'RAJESH BHAI', 'LAKHANI SADIK BHAI', 'KUVADIYA BALA BHAI', 'JADEJA ARJUN BHAI', 'ASIKBHAI BHAS'],
            'NESDA' => ['TO NIRMALA BEN', 'HAVALIYA GOPAL', 'DUDHAKIYA BHAVU BEN'],
            'BOILER & LABOUR' => ['ARJUN BHAI', 'VIJAY BHAI (DAY)', 'VIJAY BHAI (NIGHT)', 'Suresh Bhai'],
            'GENERAL' => ['RATHOD AJAY', 'DUDHAKIYA DINESH BHAI', 'DERAIYA SAHIL']
        ];

        $workerModels = [];

        foreach ($depts as $deptName => $workers) {
            $dept = Department::firstOrCreate(['name' => $deptName]);
            
            foreach ($workers as $name) {
                $workerModels[] = Worker::create([
                    'name' => $name,
                    'department_id' => $dept->id,
                    'role' => $deptName === 'STAFF' ? 'Staff' : 'Worker',
                    'shift_type' => str_contains($name, 'NIGHT') ? 'NIGHT' : 'DAY',
                    'daily_salary' => 500, // Default salary for seeding
                    'status' => 'ACTIVE'
                ]);
            }
        }

        // 2. Feed Attendance for April 2026 based on the provided sheet
        // We'll apply this specific pattern to 'ASIKBHAI BHAS' (Staff/Office)
        $targetWorker = Worker::where('name', 'ASIKBHAI BHAS')->first();
        
        $sheetData = [
            '2026-04-01' => ['in' => '09:39', 'out' => '18:45', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-02' => ['in' => '09:40', 'out' => '18:50', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-03' => ['in' => '09:19', 'out' => '18:46', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-04' => ['in' => '09:46', 'out' => '18:15', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-06' => ['in' => '08:08', 'out' => '18:45', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-07' => ['in' => '09:34', 'out' => '18:45', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-08' => ['in' => '09:44', 'out' => '14:33', 'bin' => '13:30', 'bout' => '14:30'], // Half day example
            '2026-04-09' => ['in' => '09:38', 'out' => '18:50', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-10' => ['in' => '09:34', 'out' => '18:55', 'bin' => '13:00', 'bout' => '14:10'],
            '2026-04-11' => ['in' => '09:40', 'out' => '18:46', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-13' => ['in' => '09:50', 'out' => '18:50', 'bin' => '13:45', 'bout' => '14:00'],
            '2026-04-14' => ['in' => '09:00', 'out' => '18:55', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-15' => ['in' => '09:40', 'out' => '18:45', 'bin' => '13:10', 'bout' => '15:30'],
            '2026-04-16' => ['in' => '09:34', 'out' => '21:00', 'bin' => '13:58', 'bout' => '14:20'], // Overtime
            '2026-04-17' => ['in' => '09:45', 'out' => '19:00', 'bin' => '12:35', 'bout' => '14:30'],
            '2026-04-18' => ['in' => '09:40', 'out' => '18:45', 'bin' => '13:40', 'bout' => '14:30'],
            '2026-04-20' => ['in' => '09:00', 'out' => '18:00', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-21' => ['in' => '09:38', 'out' => '19:36', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-22' => ['in' => '09:24', 'out' => '18:45', 'bin' => '13:30', 'bout' => '14:30'],
            '2026-04-23' => ['in' => '09:10', 'out' => '19:00', 'bin' => '13:35', 'bout' => '14:30'],
            '2026-04-24' => ['in' => '10:20', 'out' => '18:45', 'bin' => '13:00', 'bout' => '14:40'],
        ];

        foreach ($sheetData as $date => $times) {
            $in = Carbon::parse($times['in']);
            $out = Carbon::parse($times['out']);
            $bin = Carbon::parse($times['bin']);
            $bout = Carbon::parse($times['bout']);
            
            // Total hours = (Out - In) - (BreakOut - BreakIn)
            $workingSeconds = max(0, $out->diffInSeconds($in) - $bout->diffInSeconds($bin));
            $totalHours = round($workingSeconds / 3600, 2);
            $ot = max(0, $totalHours - 8); // Assume 8hr standard shift
            $wage = max(0, ($totalHours / 8) * 500);

            Attendance::create([
                'worker_id' => $targetWorker->id,
                'date' => $date,
                'in_time' => $times['in'],
                'out_time' => $times['out'],
                'break_in' => $times['bin'],
                'break_out' => $times['bout'],
                'total_hours' => $totalHours,
                'overtime_hours' => $ot,
                'status' => 'PRESENT',
                'calculated_wage' => $wage
            ]);
        }

        // 3. Populate generic attendance for other workers for 22/04/2026 (from the first image)
        $workers22 = Worker::where('id', '!=', $targetWorker->id)->get();
        foreach ($workers22 as $w) {
            $status = 'PRESENT';
            $inTime = '08:00';
            $outTime = '18:00';
            
            // Specific overrides from image text
            if (str_contains($w->name, 'JENTI BHAI') || str_contains($w->name, 'HASNAIN')) {
                $status = 'ABSENT';
                $inTime = null;
                $outTime = null;
            } elseif (str_contains($w->name, 'ABBAS BHAI') || str_contains($w->name, 'JAVED BHAI')) {
                $inTime = '10:49';
                $outTime = '19:09';
            } elseif (str_contains($w->name, 'AJAY')) {
                $inTime = '08:00';
                $outTime = '18:00';
            }

            if ($status === 'PRESENT') {
                Attendance::create([
                    'worker_id' => $w->id,
                    'date' => '2026-04-22',
                    'in_time' => $inTime,
                    'out_time' => $outTime,
                    'total_hours' => 10,
                    'overtime_hours' => 2,
                    'status' => 'PRESENT',
                    'calculated_wage' => (10/8) * 500
                ]);
            } else {
                Attendance::create([
                    'worker_id' => $w->id,
                    'date' => '2026-04-22',
                    'status' => 'ABSENT'
                ]);
            }
        }
    }
}
