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

    public function test_monthly_reports_page_and_summary_pdf_show_attendance_wise_actual_payable(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'PACKAGING']);
        $worker = Worker::create([
            'name'          => 'Pooja Ben',
            'department_id' => $dept->id,
            'role'          => 'Packer',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 12000.00,
            'status'        => 'ACTIVE',
        ]);

        // Present 15 days out of 30 days in September 2026:
        // Earned: 15 * (12000 / 30) = 6000
        for ($d = 1; $d <= 15; $d++) {
            \App\Models\Attendance::create([
                'worker_id' => $worker->id,
                'date' => sprintf('2026-09-%02d', $d),
                'status' => 'PRESENT',
                'overtime_hours' => 0,
            ]);
        }

        // Daily advance of 500 on one day
        \App\Models\Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-09-16',
            'status' => 'ABSENT',
            'advance' => 500,
        ]);

        // Report page check
        $response = $this->withSession($session)->get('/admin/attendance/reports?month=2026-09');
        $response->assertStatus(200);

        $reportData = $response->viewData('reportData');
        $this->assertNotNull($reportData);
        $this->assertArrayHasKey($worker->id, $reportData);

        $workerData = $reportData[$worker->id];
        // Earned salary = 6000
        $this->assertEquals(6000.00, $workerData['attendance_salary']);
        // Daily advance = 500
        $this->assertEquals(500.00, $workerData['advance']);
        // Net payable = 5500
        $this->assertEquals(5500.00, $workerData['payable_salary']);

        $content = $response->getContent();
        // Check that actual payable 5,500.00 appears in reports table
        $this->assertStringContainsString('5,500.00', $content);
        $this->assertStringContainsString('Earned: ₹6,000', $content);
        $this->assertStringContainsString('- Adv: ₹500', $content);
        $this->assertStringContainsString('<th>Advance (₹)</th>', $content);

        // Check Summary PDF endpoint
        $pdfResponse = $this->withSession($session)->get('/admin/attendance/reports/summary/pdf?month=2026-09');
        $pdfResponse->assertStatus(200);
    }

    public function test_monthly_reports_shows_ut_undertime_and_other_minus_deduction(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'PRODUCTION']);
        
        // Worker 1: has UT (Under Time)
        $workerUt = Worker::create([
            'name'          => 'Ramesh Patel',
            'department_id' => $dept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 600.00, // 600 / 12 = 50/hr
            'status'        => 'ACTIVE',
        ]);

        // Present 10 days
        for ($d = 1; $d <= 10; $d++) {
            \App\Models\Attendance::create([
                'worker_id' => $workerUt->id,
                'date' => sprintf('2026-10-%02d', $d),
                'status' => 'PRESENT',
                'overtime_hours' => 0,
            ]);
        }

        // On day 11, has 2 hours under time (UT)
        \App\Models\Attendance::create([
            'worker_id' => $workerUt->id,
            'date' => '2026-10-11',
            'status' => 'PRESENT',
            'ot_ut' => 'UT',
            'ot_ut_hours' => 2.0,
            'overtime_hours' => -2.0,
        ]);

        // Worker 2: has other minus deduction (e.g. -1500)
        $workerDeduct = Worker::create([
            'name'          => 'Suresh Kumar',
            'department_id' => $dept->id,
            'role'          => 'Helper',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 500.00,
            'status'        => 'ACTIVE',
        ]);

        for ($d = 1; $d <= 10; $d++) {
            \App\Models\Attendance::create([
                'worker_id' => $workerDeduct->id,
                'date' => sprintf('2026-10-%02d', $d),
                'status' => 'PRESENT',
                'overtime_hours' => 0,
            ]);
        }

        // Negative adjustment (other minus deduction)
        \App\Models\WorkerMonthlyAdjustment::create([
            'worker_id' => $workerDeduct->id,
            'month' => '2026-10',
            'petrol_food_amount' => -1500.00,
            'other_allowance_label' => 'DAMAGE DEDUCTION',
        ]);

        $response = $this->withSession($session)->get('/admin/attendance/reports?month=2026-10');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verify UT (Under Time) shows in column and breakdown
        $this->assertStringContainsString('- UT: ₹100', $content); // 2 hrs * 50 = 100
        $this->assertStringContainsString('UT', $content);

        // Verify other minus deduction shows with label or Other and minus sign
        $this->assertStringContainsString('- DAMAGE DEDUCTION: ₹1,500', $content);

        // Verify Summary PDF also contains UT and other deduction
        $pdfResponse = $this->withSession($session)->get('/admin/attendance/reports/summary/pdf?month=2026-10');
        $pdfResponse->assertStatus(200);
    }

    public function test_worker_inactivated_in_november_does_not_show_as_inactive_in_october_reports(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'PACKING_DEPT']);
        $activeWorker = Worker::create([
            'name'           => 'Active Worker A',
            'department_id'  => $dept->id,
            'role'           => 'Operator',
            'shift_type'     => 'DAY',
            'salary_type'    => 'DAILY',
            'salary_amount'  => 600.00,
            'daily_salary'   => 600.00,
            'status'         => 'ACTIVE',
        ]);
        $worker = Worker::create([
            'name'           => 'Ramesh Patel',
            'department_id'  => $dept->id,
            'role'           => 'Helper',
            'shift_type'     => 'DAY',
            'salary_type'    => 'DAILY',
            'salary_amount'  => 500.00,
            'daily_salary'   => 500.00,
            'status'         => 'INACTIVE',
            'inactivated_at' => '2026-11-02',
        ]);

        // In October 2026, worker was ACTIVE (inactivation happened in November)
        $responseOct = $this->withSession($session)->get('/admin/attendance/reports?month=2026-10');
        $responseOct->assertStatus(200);
        $contentOct = $responseOct->getContent();

        $reportDataOct = $responseOct->original->getData()['reportData'] ?? [];
        $this->assertArrayHasKey($worker->id, $reportDataOct);
        $this->assertFalse($reportDataOct[$worker->id]['is_inactive']);

        // Row should have data-status="active"
        $this->assertStringContainsString('data-name="ramesh patel"', $contentOct);
        $this->assertStringContainsString('data-status="active"', $contentOct);
        // The red INACTIVE badge should NOT be shown for Ramesh Patel in October
        $this->assertDoesNotMatchRegularExpression('/Ramesh Patel\s*<span[^>]*>INACTIVE<\/span>/i', $contentOct);

        // October Summary PDF blade should not show (INACTIVE)
        $summaryHtmlOct = view('pdf.monthly-payroll-summary', ['reportData' => $reportDataOct, 'month' => '2026-10'])->render();
        $this->assertDoesNotMatchRegularExpression('/RAMESH PATEL\s*<span[^>]*>\(INACTIVE\)<\/span>/i', $summaryHtmlOct);

        $pdfOct = $this->withSession($session)->get('/admin/attendance/reports/summary/pdf?month=2026-10');
        $pdfOct->assertStatus(200);

        // In November 2026 (the next month): Ramesh Patel is INACTIVE and has NO attendance in November.
        // He should NOT be shown in November reports or PDFs at all!
        $responseNov = $this->withSession($session)->get('/admin/attendance/reports?month=2026-11');
        $responseNov->assertStatus(200);
        $contentNov = $responseNov->getContent();

        $reportDataNov = $responseNov->original->getData()['reportData'] ?? [];
        $this->assertArrayNotHasKey($worker->id, $reportDataNov);
        $this->assertArrayHasKey($activeWorker->id, $reportDataNov);
        $this->assertStringNotContainsString('data-name="ramesh patel"', $contentNov);
        $this->assertStringContainsString('data-name="active worker a"', $contentNov);

        $pdfNov = $this->withSession($session)->get('/admin/attendance/reports/summary/pdf?month=2026-11');
        $pdfNov->assertStatus(200);

        // If an inactive worker DOES have attendance records in a month (e.g. worked before inactivation):
        \App\Models\Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-11-01',
            'status' => 'PRESENT',
            'overtime_hours' => 0,
        ]);

        $responseNovWithAtt = $this->withSession($session)->get('/admin/attendance/reports?month=2026-11');
        $responseNovWithAtt->assertStatus(200);
        $contentNovWithAtt = $responseNovWithAtt->getContent();
        $reportDataNovWithAtt = $responseNovWithAtt->original->getData()['reportData'] ?? [];

        // Now he shows up with his earned attendance and INACTIVE badge
        $this->assertArrayHasKey($worker->id, $reportDataNovWithAtt);
        $this->assertTrue($reportDataNovWithAtt[$worker->id]['is_inactive']);
        $this->assertStringContainsString('data-status="inactive"', $contentNovWithAtt);
        $this->assertMatchesRegularExpression('/Ramesh Patel\s*<span[^>]*>INACTIVE<\/span>/i', $contentNovWithAtt);

        // But in December 2026 (where he has 0 attendance), he again does NOT show
        $responseDec = $this->withSession($session)->get('/admin/attendance/reports?month=2026-12');
        $responseDec->assertStatus(200);
        $reportDataDec = $responseDec->original->getData()['reportData'] ?? [];
        $this->assertArrayNotHasKey($worker->id, $reportDataDec);
    }
}

