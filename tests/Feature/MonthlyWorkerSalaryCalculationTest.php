<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyWorkerSalaryCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name'     => 'Admin Test',
            'username' => 'admin_test_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => 'ADMIN',
            'branch'   => 'Main Branch',
            'status'   => 'ACTIVE',
        ]);
    }

    public function test_monthly_worker_salary_sheet_calculates_per_day_and_per_hour_rate(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'OFFICE']);
        $worker = Worker::create([
            'name'          => 'Lakhani Shakilbhai',
            'department_id' => $dept->id,
            'role'          => 'Staff',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 17000.00,
            'daily_salary'  => 566.67,
            'status'        => 'ACTIVE',
        ]);

        // September 2026 has 30 days
        // Per day: 17,000 / 30 = 566.67
        // Per hour: 566.66667 / 12 = 47.22 (previously divided by 9 which gave 62.96)
        $response = $this->withSession($session)->get("/admin/attendance/reports/worker/{$worker->id}?month=2026-09");
        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringContainsString('566.67', $content); // PER DAY
        $this->assertStringContainsString('47.22', $content);  // PER HOUR
        $this->assertStringNotContainsString('62.96', $content); // Old incorrect 9-hour rate

        // October 2026 has 31 days
        // Per day: 17,000 / 31 = 548.39
        // Per hour: 548.387 / 12 = 45.70
        $responseOct = $this->withSession($session)->get("/admin/attendance/reports/worker/{$worker->id}?month=2026-10");
        $responseOct->assertStatus(200);

        $contentOct = $responseOct->getContent();
        $this->assertStringContainsString('548.39', $contentOct); // PER DAY
        $this->assertStringContainsString('45.70', $contentOct);  // PER HOUR
    }

    public function test_worker_10000_monthly_salary_rounds_off_cleanly(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'FACTORY']);
        $worker = Worker::create([
            'name'          => 'Test Worker 15',
            'department_id' => $dept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 10000.00,
            'status'        => 'ACTIVE',
        ]);

        // Create 30 present days for 2026-09
        for ($d = 1; $d <= 30; $d++) {
            $date = sprintf('2026-09-%02d', $d);
            \App\Models\Attendance::create([
                'worker_id' => $worker->id,
                'date' => $date,
                'status' => 'PRESENT',
                'overtime_hours' => 0,
            ]);
        }

        $response = $this->withSession($session)->get("/admin/attendance/reports/worker/{$worker->id}?month=2026-09");
        $response->assertStatus(200);

        $viewData = $response->viewData();
        $this->assertEquals(10000.00, $viewData['totalWage']);
        $this->assertEquals(0.00, $viewData['totalAdvance']);
        $this->assertEquals(10000.00, $viewData['payableSalary']);

        $content = $response->getContent();
        $this->assertStringContainsString('10,000.00', $content);

        // Also test PDF export endpoint
        $pdfResponse = $this->withSession($session)->get("/admin/attendance/reports/worker/{$worker->id}/pdf?month=2026-09");
        $pdfResponse->assertStatus(200);
    }

    public function test_worker_salary_sheet_rounds_off_odd_days_attendance(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'FACTORY']);
        $worker = Worker::create([
            'name'          => 'Test Worker Odd',
            'department_id' => $dept->id,
            'role'          => 'Worker',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 10000.00,
            'status'        => 'ACTIVE',
        ]);

        // 29 days present: 29 * (10000 / 30) = 9666.67
        for ($d = 1; $d <= 29; $d++) {
            $date = sprintf('2026-09-%02d', $d);
            \App\Models\Attendance::create([
                'worker_id' => $worker->id,
                'date' => $date,
                'status' => 'PRESENT',
                'overtime_hours' => 0,
            ]);
        }

        $response = $this->withSession($session)->get("/admin/attendance/reports/worker/{$worker->id}?month=2026-09");
        $response->assertStatus(200);

        $viewData = $response->viewData();
        // 9666.6666... rounded to nearest integer is 9667
        $this->assertEquals(9667.00, $viewData['totalWage']);
        $this->assertEquals(9667.00, $viewData['payableSalary']);

        $content = $response->getContent();
        $this->assertStringContainsString('9,667.00', $content);
    }
}
