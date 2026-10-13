<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Worker;
use App\Models\AttendanceSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubAdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_admin_with_edit_attendance_cannot_modify_submitted_attendance_but_can_modify_pending()
    {
        $dept = Department::create(['name' => 'Production', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'Test Worker',
            'department_id' => $dept->id,
            'role' => 'Operator',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_amount' => 500,
            'daily_salary' => 500,
            'status' => 'ACTIVE'
        ]);

        $subAdmin = User::create([
            'name' => 'Sub Admin HR',
            'username' => 'subadmin_hr',
            'phone' => '+91 9898000020',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_attendance_daily', 'edit_attendance_daily']
        ]);

        // 1. Create a SUBMITTED submission -> Sub-Admin cannot modify it
        $dateSubmitted = '2026-10-10';
        AttendanceSubmission::create([
            'attendance_date' => $dateSubmitted,
            'status' => 'SUBMITTED'
        ]);

        $responseSubmitted = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => $dateSubmitted,
                'save_mode' => 'partial',
                'attendances' => [
                    [
                        'worker_id' => $worker->id,
                        'status' => 'PRESENT',
                        'in_time' => '09:00',
                        'out_time' => '18:00',
                        'shift_type' => 'DAY',
                        'ot_ut' => 'NONE',
                        'ot_ut_hours' => 0
                    ]
                ]
            ]);

        $responseSubmitted->assertStatus(403);
        $responseSubmitted->assertJson(['success' => false, 'message' => 'Attendance is locked and can only be modified by Admin.']);

        // 2. Pending attendance -> Sub-Admin CAN modify it
        $datePending = '2026-10-15';
        $responsePending = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => $datePending,
                'save_mode' => 'partial',
                'attendances' => [
                    [
                        'worker_id' => $worker->id,
                        'status' => 'PRESENT',
                        'in_time' => '09:00',
                        'out_time' => '18:00',
                        'shift_type' => 'DAY',
                        'ot_ut' => 'NONE',
                        'ot_ut_hours' => 0
                    ]
                ]
            ]);

        $responsePending->assertStatus(200);
        $responsePending->assertJson(['success' => true]);
    }

    public function test_sub_admin_with_view_only_cannot_modify_attendance()
    {
        $dept = Department::create(['name' => 'Boiler', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'Boiler Worker',
            'department_id' => $dept->id,
            'role' => 'Operator',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_amount' => 500,
            'daily_salary' => 500,
            'status' => 'ACTIVE'
        ]);

        $subAdmin = User::create([
            'name' => 'View Only Sub Admin',
            'username' => 'subadmin_view',
            'phone' => '+91 9898000021',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_attendance_daily']
        ]);

        $date = '2026-10-11';
        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => $date,
                'save_mode' => 'partial',
                'attendances' => [
                    [
                        'worker_id' => $worker->id,
                        'status' => 'PRESENT',
                        'in_time' => '09:00',
                        'out_time' => '18:00',
                        'shift_type' => 'DAY',
                        'ot_ut' => 'NONE',
                        'ot_ut_hours' => 0
                    ]
                ]
            ]);

        $response->assertStatus(403);
    }

    public function test_sub_admin_with_legacy_attendance_daily_can_modify_attendance()
    {
        $dept = Department::create(['name' => 'Legacy Dept', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'Legacy Worker',
            'department_id' => $dept->id,
            'role' => 'Operator',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_amount' => 500,
            'daily_salary' => 500,
            'status' => 'ACTIVE'
        ]);

        // SubAdmin with non-prefixed legacy permission
        $subAdmin = User::create([
            'name' => 'Legacy Sub Admin',
            'username' => 'subadmin_legacy',
            'phone' => '+91 9898000022',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['attendance_daily']
        ]);

        $date = '2026-10-12';
        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => $date,
                'save_mode' => 'partial',
                'attendances' => [
                    [
                        'worker_id' => $worker->id,
                        'status' => 'PRESENT',
                        'in_time' => '09:00',
                        'out_time' => '18:00',
                        'shift_type' => 'DAY',
                        'ot_ut' => 'NONE',
                        'ot_ut_hours' => 0
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_sub_admin_with_edit_departments_can_create_and_update_department()
    {
        $subAdmin = User::create([
            'name' => 'Dept Sub Admin',
            'username' => 'subadmin_dept',
            'phone' => '+91 9898000023',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_attendance_departments', 'edit_attendance_departments']
        ]);

        // Create department
        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/departments', [
                'name' => 'Testing Dept',
                'is_active' => true
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('departments', ['name' => 'Testing Dept']);
    }

    public function test_sub_admin_with_edit_workers_can_create_worker()
    {
        $dept = Department::create(['name' => 'Worker Dept', 'is_active' => true]);
        $subAdmin = User::create([
            'name' => 'Worker Sub Admin',
            'username' => 'subadmin_worker',
            'phone' => '+91 9898000024',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_attendance_workers', 'edit_attendance_workers']
        ]);

        // Create worker
        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/attendance/workers', [
                'name' => 'New Worker 1',
                'department_id' => $dept->id,
                'role' => 'Technician',
                'shift_type' => 'DAY',
                'salary_type' => 'DAILY',
                'salary_amount' => 600,
                'status' => 'ACTIVE'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('workers', ['name' => 'New Worker 1']);
    }

    public function test_sub_admin_root_redirects_to_home()
    {
        $subAdmin = User::create([
            'name' => 'Sub Admin Root',
            'username' => 'subadmin_root',
            'phone' => '+91 9898000025',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_stock_manager_home', 'edit_stock_manager_home']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin');
        $response->assertRedirect('/sub_admin/home');

        $responseSlash = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/');
        $responseSlash->assertRedirect('/sub_admin/home');
    }

    public function test_sub_admin_with_assigned_departments_can_only_access_their_departments()
    {
        $dept1 = Department::create(['name' => 'Assigned Dept', 'is_active' => true]);
        $dept2 = Department::create(['name' => 'Other Dept', 'is_active' => true]);

        $subAdmin = User::create([
            'name' => 'Assigned Dept Sub Admin',
            'username' => 'subadmin_assigned',
            'phone' => '+91 9898000026',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => [$dept1->id]
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/attendance/departments');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Assigned Dept', $content);
        $this->assertStringNotContainsString('Other Dept', $content);
    }

    public function test_sub_admin_with_view_attendance_daily_can_view_daily_page_without_403()
    {
        $dept = Department::create(['name' => 'View Dept', 'is_active' => true]);
        $subAdmin = User::create([
            'name' => 'Daily View Sub Admin',
            'username' => 'subadmin_daily_view',
            'phone' => '+91 9898000027',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_attendance_daily']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/attendance/daily?date=2026-10-13');

        $response->assertStatus(200);
        $this->assertTrue(view()->shared('isReadOnly'));
    }

    public function test_sub_admin_with_assigned_dept_and_dashboard_view_can_view_daily_page_without_403()
    {
        $dept = Department::create(['name' => 'Assigned Dept 2', 'is_active' => true]);
        $subAdmin = User::create([
            'name' => 'Dept Dashboard Sub Admin',
            'username' => 'subadmin_dept_dash',
            'phone' => '+91 9898000028',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => [$dept->id, 'view_attendance_dashboard']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/attendance/daily?date=2026-10-13');

        $response->assertStatus(200);
        $this->assertTrue(view()->shared('isReadOnly'));
    }
}

