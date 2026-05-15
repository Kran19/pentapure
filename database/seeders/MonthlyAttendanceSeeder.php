<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Worker;
use App\Models\Attendance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have a cashier user for transactions
        $cashier = User::where('role', 'CASHIER')->first() ?? User::where('role', 'ADMIN')->first();

        // 1. Create Departments and Workers if they don't exist
        $depts = [
            'OFFICE' => [
                ['name' => 'Vedant Patel', 'salary' => 45000, 'type' => 'MONTHLY', 'role' => 'Manager'],
                ['name' => 'Amit Sharma', 'salary' => 35000, 'type' => 'MONTHLY', 'role' => 'Accountant'],
                ['name' => 'Sneha Gupta', 'salary' => 30000, 'type' => 'MONTHLY', 'role' => 'HR Executive'],
            ],
            'PRODUCTION' => [
                ['name' => 'Rahul Kumar', 'salary' => 700, 'type' => 'DAILY', 'role' => 'Operator'],
                ['name' => 'Vikram Singh', 'salary' => 650, 'type' => 'DAILY', 'role' => 'Operator'],
                ['name' => 'Raj Verma', 'salary' => 600, 'type' => 'DAILY', 'role' => 'Helper'],
                ['name' => 'Suresh Bhai', 'salary' => 800, 'type' => 'DAILY', 'role' => 'Supervisor'],
                ['name' => 'Arjun Bhai', 'salary' => 750, 'type' => 'DAILY', 'role' => 'Technician'],
            ],
            'MAINTENANCE' => [
                ['name' => 'Vijay Bhai', 'salary' => 600, 'type' => 'DAILY', 'role' => 'Electrician'],
                ['name' => 'Sahil Deraiya', 'salary' => 550, 'type' => 'DAILY', 'role' => 'Plumber'],
                ['name' => 'Dinesh Bhai', 'salary' => 500, 'type' => 'DAILY', 'role' => 'Cleaner'],
            ],
            'LOGISTICS' => [
                ['name' => 'Ravi Kishor', 'salary' => 600, 'type' => 'DAILY', 'role' => 'Driver'],
                ['name' => 'Mamad Jafar', 'salary' => 500, 'type' => 'DAILY', 'role' => 'Loader'],
                ['name' => 'Bhavsang Bhai', 'salary' => 500, 'type' => 'DAILY', 'role' => 'Loader'],
            ]
        ];

        foreach ($depts as $deptName => $workers) {
            $dept = Department::firstOrCreate(['name' => $deptName]);
            
            foreach ($workers as $wData) {
                Worker::updateOrCreate(
                    ['name' => $wData['name']],
                    [
                        'department_id' => $dept->id,
                        'role' => $wData['role'],
                        'shift_type' => 'DAY',
                        'salary_type' => $wData['type'],
                        'salary_amount' => $wData['salary'],
                        'daily_salary' => $wData['type'] === 'DAILY' ? $wData['salary'] : round($wData['salary'] / 26, 2),
                        'status' => 'ACTIVE'
                    ]
                );
            }
        }

        $allWorkers = Worker::all();
        $startDate = Carbon::create(2026, 5, 1);
        $endDate = Carbon::now(); // Today is May 15, 2026

        // Clear existing attendance for this range to avoid duplicates
        Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->delete();

        foreach ($allWorkers as $worker) {
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Skip Sundays
                if ($currentDate->isSunday()) {
                    $currentDate->addDay();
                    continue;
                }

                $rand = rand(1, 100);
                
                if ($rand <= 88) { // 88% Present
                    // Randomize arrival between 8:30 and 9:30
                    $inTime = $currentDate->copy()->setTime(8, rand(30, 59), 0);
                    if (rand(1, 10) > 8) $inTime->setTime(9, rand(0, 30), 0); // Some late arrivals

                    // Randomize departure between 17:30 and 19:30
                    $outTime = $currentDate->copy()->setTime(18, rand(0, 30), 0);
                    if (rand(1, 10) > 7) $outTime->setTime(19, rand(0, 59), 0); // Some overtime

                    // Fixed break
                    $bin = $currentDate->copy()->setTime(13, 0, 0);
                    $bout = $currentDate->copy()->setTime(14, 0, 0);
                    
                    $workingSeconds = $outTime->diffInSeconds($inTime) - $bout->diffInSeconds($bin);
                    $totalHours = round($workingSeconds / 3600, 2);
                    $ot = max(0, $totalHours - 8);
                    
                    // Wage calculation
                    if ($worker->salary_type === 'DAILY') {
                        $wage = ($totalHours / 8) * $worker->salary_amount;
                    } else {
                        // Monthly workers get their daily rate if present
                        $wage = $worker->salary_amount / 26;
                    }

                    Attendance::create([
                        'worker_id' => $worker->id,
                        'date' => $currentDate->toDateString(),
                        'in_time' => $inTime->toTimeString(),
                        'out_time' => $outTime->toTimeString(),
                        'break_in' => $bin->toTimeString(),
                        'break_out' => $bout->toTimeString(),
                        'total_hours' => $totalHours,
                        'overtime_hours' => $ot,
                        'status' => 'PRESENT',
                        'calculated_wage' => round($wage, 2)
                    ]);
                } elseif ($rand <= 94) { // 6% Half Day
                    $inTime = $currentDate->copy()->setTime(9, 0, 0);
                    $outTime = $currentDate->copy()->setTime(13, 30, 0);
                    
                    $totalHours = 4.5;
                    $ot = 0;
                    
                    if ($worker->salary_type === 'DAILY') {
                        $wage = $worker->salary_amount / 2;
                    } else {
                        $wage = ($worker->salary_amount / 26) / 2;
                    }

                    Attendance::create([
                        'worker_id' => $worker->id,
                        'date' => $currentDate->toDateString(),
                        'in_time' => $inTime->toTimeString(),
                        'out_time' => $outTime->toTimeString(),
                        'total_hours' => $totalHours,
                        'overtime_hours' => $ot,
                        'status' => 'HALF_DAY',
                        'calculated_wage' => round($wage, 2)
                    ]);
                } else { // 6% Absent
                    Attendance::create([
                        'worker_id' => $worker->id,
                        'date' => $currentDate->toDateString(),
                        'status' => 'ABSENT',
                        'calculated_wage' => 0,
                        'total_hours' => 0,
                        'overtime_hours' => 0
                    ]);
                }
                
                $currentDate->addDay();
            }
        }

        // 3. Add some HR related transactions (Advanced payments or previous month salary)
        if ($cashier) {
            // Add some "Salary Advance" transactions
            $advanceWorkers = $allWorkers->random(3);
            foreach ($advanceWorkers as $aw) {
                Transaction::create([
                    'user_id' => $cashier->id,
                    'type' => 'OUT',
                    'amount' => rand(1000, 5000),
                    'category' => 'Salary Advance',
                    'note' => 'Advanced salary for ' . $aw->name,
                    'date' => Carbon::now()->subDays(rand(1, 10))
                ]);
            }

            // Add "April Salary" total as a single transaction or multiple
            Transaction::create([
                'user_id' => $cashier->id,
                'type' => 'OUT',
                'amount' => 150000,
                'category' => 'Payroll',
                'note' => 'April 2026 total staff salary distribution',
                'date' => Carbon::create(2026, 5, 2)
            ]);
        }
    }
}
