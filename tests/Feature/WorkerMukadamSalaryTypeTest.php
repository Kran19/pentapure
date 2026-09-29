<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerMukadamSalaryTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::create([
            'name'     => "User {$role}",
            'username' => strtolower($role) . '_' . uniqid(),
            'email'    => strtolower($role) . '_' . uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => $role,
            'branch'   => 'Main Branch',
            'status'   => 'ACTIVE',
        ]);
    }

    public function test_workers_and_dashboard_views_contain_mukadam_sync_scripts(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $sessionAttendance = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $admin = $this->createUser('ADMIN');
        $sessionAdmin = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        Department::create(['name' => 'MUKADAM']);
        Department::create(['name' => 'PRODUCTION']);

        // Check workers page for attendance role (/attendance/workers)
        $resWorkers = $this->withSession($sessionAttendance)->get('/attendance/workers');
        $resWorkers->assertStatus(200);
        $resWorkers->assertSee('handleDepartmentChange');
        $resWorkers->assertSee('MUKADAM (₹ / LABOUR)');

        // Check workers page for admin role (/admin/attendance/workers)
        $resAdminWorkers = $this->withSession($sessionAdmin)->get('/admin/attendance/workers');
        $resAdminWorkers->assertStatus(200);
        $resAdminWorkers->assertSee('handleDepartmentChange');
        $resAdminWorkers->assertSee('MUKADAM (₹ / LABOUR)');

        // Check admin attendance dashboard page (/admin/attendance/dashboard)
        $resDashboard = $this->withSession($sessionAdmin)->get('/admin/attendance/dashboard');
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('handleModalDepartmentChange');
        $resDashboard->assertSee('MUKADAM (₹ / LABOUR)');
    }

    public function test_storing_worker_with_mukadam_department_assigns_labour_mukadam_salary_type(): void
    {
        $admin = $this->createUser('ADMIN');
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $mukadamDept = Department::create(['name' => 'MUKADAM']);

        $response = $this->withSession($session)->postJson('/admin/attendance/workers', [
            'name'          => 'Mukadam Worker 1',
            'department_id' => $mukadamDept->id,
            'role'          => 'Mukadam',
            'shift_type'    => 'DAY',
            'salary_type'   => 'LABOUR_MUKADAM',
            'salary_amount' => 500,
            'status'        => 'ACTIVE',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $worker = Worker::where('name', 'Mukadam Worker 1')->first();
        $this->assertNotNull($worker);
        $this->assertEquals('LABOUR_MUKADAM', $worker->salary_type);
        $this->assertEquals($mukadamDept->id, $worker->department_id);
    }

    public function test_monthly_salary_type_hides_per_hour_salary_and_clears_it(): void
    {
        $admin = $this->createUser('ADMIN');
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'PRODUCTION']);

        // Check workers view script
        $resWorkers = $this->withSession($session)->get('/admin/attendance/workers');
        $resWorkers->assertStatus(200);
        $resWorkers->assertSee("const isMonthly = (type === 'MONTHLY' || type === 'FIXED_MONTHLY');", false);
        $resWorkers->assertSee("perHourGroup.style.display = isMonthly ? 'none' : 'block';", false);

        // Check dashboard modal script
        $resDashboard = $this->withSession($session)->get('/admin/attendance/dashboard');
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee("const isMonthly = (type === 'MONTHLY' || type === 'FIXED_MONTHLY');", false);
        $resDashboard->assertSee("perHourGroup.style.display = isMonthly ? 'none' : 'block';", false);

        // Storing a monthly worker should set per_hour_salary to null
        $response = $this->withSession($session)->postJson('/admin/attendance/workers', [
            'name'            => 'Monthly Worker 1',
            'department_id'   => $dept->id,
            'role'            => 'Supervisor',
            'shift_type'      => 'DAY',
            'salary_type'     => 'MONTHLY',
            'salary_amount'   => 30000,
            'per_hour_salary' => 150, // Should be cleared to null
            'status'          => 'ACTIVE',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $worker = Worker::where('name', 'Monthly Worker 1')->first();
        $this->assertNotNull($worker);
        $this->assertEquals('MONTHLY', $worker->salary_type);
        $this->assertEquals(30000, (float)$worker->salary_amount);
        $this->assertEquals(1000, (float)$worker->daily_salary);
        $this->assertNull($worker->per_hour_salary);
    }

    public function test_monthly_payroll_summary_pdf_generation(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $sessionAttendance = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $dept = Department::create(['name' => 'PACKING']);
        Worker::create([
            'name'          => 'Test Worker',
            'department_id' => $dept->id,
            'role'          => 'Packer',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 15000,
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession($sessionAttendance)->get('/attendance/reports/summary/pdf?month=2026-09');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_worker_report_does_not_have_pretyped_other_or_other_cell(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $sessionAttendance = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $dept = Department::create(['name' => 'PACKING']);
        $worker = Worker::create([
            'name'          => 'Amit Sharma',
            'department_id' => $dept->id,
            'role'          => 'Accountant',
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 35000,
            'daily_salary'  => 1346.15,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession($sessionAttendance)->get("/attendance/history/worker/{$worker->id}?month=2026-09");
        $response->assertStatus(200);
        $content = $response->getContent();

        // Ensure there is no static OTHER cell or pretyped OTHER value in the allowance row
        $this->assertStringNotContainsString('<td style="border:2px solid #000; padding:6px 8px; text-align:center;">OTHER</td>', $content);
        $this->assertStringNotContainsString('value="OTHER"', $content);
        $this->assertStringNotContainsString('placeholder="OTHER"', $content);

        // Ensure the input exists with empty value
        $this->assertStringContainsString('class="allowance-label-input" value=""', $content);

        // Test saving a custom label
        $adjustResponse = $this->withSession($sessionAttendance)->post("/attendance/history/worker/{$worker->id}/adjust", [
            'month'                 => '2026-09',
            'other_allowance_label' => 'BONUS',
            'petrol_food_amount'    => 500,
            'advance'               => 0,
        ]);
        $adjustResponse->assertStatus(302);

        $adj = \App\Models\WorkerMonthlyAdjustment::where('worker_id', $worker->id)->where('month', '2026-09')->first();
        $this->assertNotNull($adj);
        $this->assertEquals('BONUS', $adj->other_allowance_label);

        // Verify the sheet now displays BONUS
        $responseAfterBonus = $this->withSession($sessionAttendance)->get("/attendance/history/worker/{$worker->id}?month=2026-09");
        $this->assertStringContainsString('value="BONUS"', $responseAfterBonus->getContent());

        // Test saving 'OTHER' or empty string converts to null
        $adjustResponseEmpty = $this->withSession($sessionAttendance)->post("/attendance/history/worker/{$worker->id}/adjust", [
            'month'                 => '2026-09',
            'other_allowance_label' => 'OTHER',
            'petrol_food_amount'    => 500,
            'advance'               => 0,
        ]);
        $adjustResponseEmpty->assertStatus(302);

        $adj->refresh();
        $this->assertNull($adj->other_allowance_label);

        // Also test PDF view
        $pdfResponse = $this->withSession($sessionAttendance)->get("/attendance/history/worker/{$worker->id}/pdf?month=2026-09");
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_attendance_reports_and_summary_pdf_display_mukadam_per_labour_salary(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $sessionAttendance = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $mukadamDept = Department::create(['name' => 'MUKADAM']);
        $mukadamWorker = Worker::create([
            'name'          => 'Mukadam Contractor',
            'department_id' => $mukadamDept->id,
            'role'          => 'Mukadam',
            'shift_type'    => 'DAY',
            'salary_type'   => 'LABOUR_MUKADAM',
            'salary_amount' => 500,
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        // Attendance with 12 labours on 2026-09-10
        \App\Models\Attendance::create([
            'worker_id'       => $mukadamWorker->id,
            'date'            => '2026-09-10',
            'status'          => 'PRESENT',
            'num_workers'     => 12,
            'calculated_wage' => 6000,
            'overtime_hours'  => 0,
        ]);

        // 1. Web report page: /attendance/reports?month=2026-09
        $reportsRes = $this->withSession($sessionAttendance)->get('/attendance/reports?month=2026-09');
        $reportsRes->assertStatus(200);
        $reportsContent = $reportsRes->getContent();

        $this->assertStringContainsString('₹500', $reportsContent);
        $this->assertStringContainsString('/ Per Labour', $reportsContent);
        $this->assertStringContainsString('PER LABOUR SALARY (LABOUR_MUKADAM)', $reportsContent);

        // 2. Summary PDF export: /attendance/reports/summary/pdf?month=2026-09
        $pdfRes = $this->withSession($sessionAttendance)->get('/attendance/reports/summary/pdf?month=2026-09');
        $pdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfRes->headers->get('Content-Type'));

        // 3. Rendered PDF view HTML verification
        $renderedPdfView = view('pdf.monthly-payroll-summary', [
            'reportData' => [
                $mukadamWorker->id => [
                    'worker'        => $mukadamWorker,
                    'worker_number' => 1,
                    'present'       => 12,
                    'absent'        => 0,
                    'half'          => 0,
                    'total_ot'      => 0,
                    'total_wage'    => 6000,
                    'adjustment'    => null,
                ]
            ],
            'month' => '2026-09'
        ])->render();

        $this->assertStringContainsString('₹500', $renderedPdfView);
        $this->assertStringContainsString('/ Per Labour', $renderedPdfView);
        $this->assertStringContainsString('PER LABOUR SALARY (LABOUR_MUKADAM)', $renderedPdfView);
    }
}

