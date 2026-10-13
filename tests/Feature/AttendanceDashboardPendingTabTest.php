<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDashboardPendingTabTest extends TestCase
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

    public function test_attendance_dashboard_displays_pending_attendance_tab_and_card(): void
    {
        $admin = $this->createUser('ADMIN');
        $sessionAdmin = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $dept = Department::create(['name' => 'PRODUCTION']);
        $worker = Worker::create([
            'name'          => 'Karan Sharma',
            'department_id' => $dept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $pendingDate1 = '2026-10-10';
        $pendingDate2 = '2026-10-11';
        $submittedDate = '2026-10-09';

        // 1. Partial saved submission
        AttendanceSubmission::create([
            'attendance_date' => $pendingDate1,
            'status'          => 'PARTIAL_SAVED',
            'created_by'      => $admin->id,
        ]);
        Attendance::create([
            'worker_id'   => $worker->id,
            'date'        => $pendingDate1,
            'status'      => 'PRESENT',
            'shift_type'  => 'DAY',
        ]);

        // 2. Pending submission
        AttendanceSubmission::create([
            'attendance_date' => $pendingDate2,
            'status'          => 'PENDING',
            'created_by'      => $admin->id,
        ]);

        // 3. Submitted submission
        AttendanceSubmission::create([
            'attendance_date' => $submittedDate,
            'status'          => 'SUBMITTED',
            'created_by'      => $admin->id,
            'submitted_by'    => $admin->id,
            'submitted_at'    => now(),
        ]);

        $response = $this->withSession($sessionAdmin)->get('/admin/attendance/dashboard');
        $response->assertStatus(200);

        // Verify Pending Attendance summary card and tab navigation exist
        $response->assertSee('Pending Attendance');
        $response->assertSee('tab-btn-pending');
        $response->assertSee('tab-btn-all');
        $response->assertSee('tab-btn-submitted');
        $response->assertSee('tab-content-pending');

        // Verify pending dates are present in the view
        $response->assertSee('10-10-2026');
        $response->assertSee('11-10-2026');
        $response->assertSee('PARTIAL SAVED');
        $response->assertSee('PENDING');

        // Verify Action button link to open/fill the daily sheet
        $response->assertSee('/daily?date=2026-10-10');
        $response->assertSee('/daily?date=2026-10-11');
        $this->assertStringContainsString('Open & Fill', $response->getContent());
    }

    public function test_sub_admin_attendance_dashboard_does_not_show_view_only_banner(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin View Only',
            'email'       => 'subadmin_view_' . uniqid() . '@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_attendance_dashboard'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $response = $this->withSession($session)->get('/sub_admin/attendance/dashboard');
        $response->assertStatus(200);

        // Verify the View-Only banner is NOT shown on the dashboard
        $response->assertDontSee('View-Only Mode: You have read-only access to this page');
    }

    public function test_sub_admin_with_edit_permission_sees_open_button_on_dashboard(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin Edit',
            'email'       => 'subadmin_edit_' . uniqid() . '@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_attendance_dashboard', 'edit_attendance_daily'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $date = '2026-10-12';
        AttendanceSubmission::create([
            'attendance_date' => $date,
            'status'          => 'SUBMITTED',
            'created_by'      => $subAdmin->id,
        ]);

        $response = $this->withSession($session)->get('/sub_admin/attendance/dashboard');
        $response->assertStatus(200);

        $content = $response->getContent();
        $response->assertSee('12-10-2026');
        $response->assertSee('SUBMITTED');
        // When sub admin has edit permission, button must be 'Open'
        $this->assertStringContainsString('/sub_admin/attendance/daily?date=2026-10-12', $content);
        $this->assertStringContainsString('>Open<', preg_replace('/\s+/', '', $content));
    }

    public function test_sub_admin_with_view_only_permission_sees_view_button_on_dashboard(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin View Only',
            'email'       => 'subadmin_view2_' . uniqid() . '@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_attendance_dashboard', 'view_attendance_daily'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $date = '2026-10-12';
        AttendanceSubmission::create([
            'attendance_date' => $date,
            'status'          => 'SUBMITTED',
            'created_by'      => $subAdmin->id,
        ]);

        $response = $this->withSession($session)->get('/sub_admin/attendance/dashboard');
        $response->assertStatus(200);

        $content = $response->getContent();
        $response->assertSee('12-10-2026');
        $response->assertSee('SUBMITTED');
        // When sub admin has view-only permission, button must be 'View' and not 'Open'
        $this->assertStringContainsString('>View<', preg_replace('/\s+/', '', $content));
        $this->assertStringNotContainsString('>Open<', preg_replace('/\s+/', '', $content));
    }

    public function test_unsubmitted_dates_appear_in_pending_attendance_tab_date_wise(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin Attendance',
            'email'       => 'subadmin_att_' . uniqid() . '@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_attendance_dashboard', 'edit_attendance_daily'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        $dept = Department::create(['name' => 'FACTORY']);
        Worker::create([
            'name'          => 'Mahesh Patel',
            'department_id' => $dept->id,
            'role'          => 'Technician',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 600,
            'status'        => 'ACTIVE',
        ]);

        // Submit attendance for 12-10-2026
        AttendanceSubmission::create([
            'attendance_date' => '2026-10-12',
            'status'          => 'SUBMITTED',
            'created_by'      => $subAdmin->id,
            'submitted_by'    => $subAdmin->id,
            'submitted_at'    => now(),
        ]);

        // Date 10-10-2026 and 11-10-2026 were NEVER submitted (no AttendanceSubmission row)
        $response = $this->withSession($session)->get('/sub_admin/attendance/dashboard?month=2026-10');
        $response->assertStatus(200);

        $content = $response->getContent();

        // 10th and 11th must appear in the pending list
        $response->assertSee('10-10-2026');
        $response->assertSee('11-10-2026');
        $this->assertStringContainsString('/daily?date=2026-10-10', $content);
        $this->assertStringContainsString('/daily?date=2026-10-11', $content);

        // Date-wise ordering: 10-10-2026 must appear before 11-10-2026 in the pending table
        $pos10 = strpos($content, '10-10-2026');
        $pos11 = strpos($content, '11-10-2026');
        $this->assertNotFalse($pos10);
        $this->assertNotFalse($pos11);
        $this->assertLessThan($pos11, $pos10, '10-10-2026 must appear before 11-10-2026 in date-wise order');

        // Now submit attendance for 10-10-2026
        AttendanceSubmission::create([
            'attendance_date' => '2026-10-10',
            'status'          => 'SUBMITTED',
            'created_by'      => $subAdmin->id,
            'submitted_by'    => $subAdmin->id,
            'submitted_at'    => now(),
        ]);

        // Re-fetch dashboard: 10-10-2026 should be submitted, only 11-10-2026 remains pending
        $responseAfter = $this->withSession($session)->get('/sub_admin/attendance/dashboard?month=2026-10');
        $responseAfter->assertStatus(200);
        $contentAfter = $responseAfter->getContent();

        // Check Pending tab section only contains 11-10-2026 as pending
        $pendingSection = substr($contentAfter, strpos($contentAfter, 'tab-content-pending'));
        $pendingSection = substr($pendingSection, 0, strpos($pendingSection, 'tab-content-all'));
        $this->assertStringContainsString('11-10-2026', $pendingSection);
        $this->assertStringNotContainsString('10-10-2026', $pendingSection);
    }

    public function test_attendance_dashboard_opens_all_records_tab_on_initial_open(): void
    {
        $admin = $this->createUser('ADMIN');
        $sessionAdmin = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $response = $this->withSession($sessionAdmin)->get('/admin/attendance/dashboard');
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. All Records tab content is visible by default (no display:none)
        $this->assertStringContainsString('<div id="tab-content-all" class="att-tab-pane">', $content);

        // 2. Pending tab content is hidden by default (style="display:none;")
        $this->assertStringContainsString('<div id="tab-content-pending" class="att-tab-pane" style="display:none;">', $content);

        // 3. Active tab hidden input defaults to 'all'
        $this->assertStringContainsString('id="activeTabInput" value="all"', $content);

        // 4. All tab button is active (has white background and shadow)
        $this->assertStringContainsString('id="tab-btn-all" class="att-tab-btn" onclick="switchAttendanceTab(\'all\')" style="display:flex; align-items:center; gap:6px; padding:0.45rem 0.9rem; border-radius:8px; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; background:#ffffff; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1);', $content);

        // 5. JS default falls back to 'all' tab
        $this->assertStringContainsString("switchAttendanceTab('all');", $content);
    }

    public function test_attendance_dashboard_all_records_shows_all_records_without_limit(): void
    {
        $subAdmin = User::create([
            'name'        => 'Sub Admin All',
            'email'       => 'subadmin_all_' . uniqid() . '@example.com',
            'password'    => 'password123',
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['view_attendance_dashboard', 'edit_attendance_daily'],
        ]);
        $session = ['auth_user' => $subAdmin->toArray()];

        // Create 55 attendance submissions to test exceeding previous 50 limit
        $startDate = Carbon::parse('2026-08-01');
        for ($i = 0; $i < 55; $i++) {
            $currDate = $startDate->copy()->addDays($i)->toDateString();
            AttendanceSubmission::create([
                'attendance_date' => $currDate,
                'status'          => $i % 2 === 0 ? 'SUBMITTED' : 'PARTIAL_SAVED',
                'created_by'      => $subAdmin->id,
            ]);
        }

        $response = $this->withSession($session)->get('/sub_admin/attendance/dashboard');
        $response->assertStatus(200);

        // Assert recentSubmissions view data contains all 55+ submissions (no 50 limit)
        $allRecords = $response->viewData('recentSubmissions');
        $this->assertGreaterThanOrEqual(55, $allRecords->count(), 'All Records tab must show all submissions without 50 limit');

        $content = $response->getContent();

        // Check earliest and latest dates from the 55 records are present
        $firstDateFormatted = Carbon::parse('2026-08-01')->format('d-m-Y');
        $lastDateFormatted  = Carbon::parse('2026-08-01')->addDays(54)->format('d-m-Y');
        $this->assertStringContainsString($firstDateFormatted, $content);
        $this->assertStringContainsString($lastDateFormatted, $content);
    }
}
