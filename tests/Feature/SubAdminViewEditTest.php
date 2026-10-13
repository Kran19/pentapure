<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Worker;
use App\Models\AttendanceSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubAdminViewEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_subadmin_view_only_ui_elements()
    {
        $dept = Department::create(['name' => 'Testing Dept', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'John Worker',
            'department_id' => $dept->id,
            'role' => 'Operator',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_amount' => 500,
            'daily_salary' => 500,
            'status' => 'ACTIVE'
        ]);

        $subAdminViewOnly = User::create([
            'name' => 'Sub Admin Viewer',
            'username' => 'subadmin_viewer',
            'phone' => '+91 9898000030',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => [
                'view_attendance_dashboard',
                'view_attendance_departments',
                'view_attendance_workers',
                'view_attendance_daily',
                'view_attendance_reports'
            ]
        ]);

        // 1. View-Only Daily Attendance UI
        $respDaily = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->get('/sub_admin/attendance/daily');
        $respDaily->assertStatus(200);
        $contentDaily = $respDaily->getContent();
        $this->assertStringNotContainsString('>Save</button>', $contentDaily);
        $this->assertStringNotContainsString('>Final Save</button>', $contentDaily);
        $this->assertStringContainsString('disabled', $contentDaily);

        // 2. View-Only Daily Attendance Mutation blocked
        $respDailyPost = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => now()->toDateString(),
                'save_mode' => 'partial',
                'attendances' => [
                    ['worker_id' => $worker->id, 'status' => 'PRESENT']
                ]
            ]);
        $respDailyPost->assertStatus(403);

        // 3. View-Only Departments UI
        $respDept = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->get('/sub_admin/attendance/departments');
        $respDept->assertStatus(200);
        $contentDept = $respDept->getContent();
        $this->assertStringNotContainsString('+ Add Department', $contentDept);
        $this->assertStringNotContainsString('title="Edit"', $contentDept);
        $this->assertStringContainsString('View Only', $contentDept);

        // 4. View-Only Departments Mutation blocked
        $respDeptPost = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->postJson('/sub_admin/attendance/departments', [
                'name' => 'Unauthorized Dept'
            ]);
        $respDeptPost->assertStatus(403);

        // 5. View-Only Workers Master UI
        $respWorker = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->get('/sub_admin/attendance/workers');
        $respWorker->assertStatus(200);
        $contentWorker = $respWorker->getContent();
        $this->assertStringNotContainsString('+ Add Worker', $contentWorker);
        $this->assertStringNotContainsString('title="Edit"', $contentWorker);
        $this->assertStringContainsString('View Only', $contentWorker);

        // 6. View-Only Workers Mutation blocked
        $respWorkerPost = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->postJson('/sub_admin/attendance/workers', [
                'name' => 'Unauthorized Worker',
                'department_id' => $dept->id,
                'shift_type' => 'DAY',
                'salary_type' => 'DAILY',
                'salary_amount' => 600,
                'status' => 'ACTIVE'
            ]);
        $respWorkerPost->assertStatus(403);

        // 7. View-Only Reports Payment Toggle blocked
        $respPaidToggle = $this->withSession(['auth_user' => $subAdminViewOnly->toArray()])
            ->postJson("/sub_admin/attendance/reports/worker/{$worker->id}/toggle-paid", [
                'month' => now()->format('Y-m'),
                'is_paid' => 1
            ]);
        $respPaidToggle->assertStatus(403);
    }

    public function test_subadmin_with_edit_permissions_can_perform_actions()
    {
        $dept = Department::create(['name' => 'Active Dept', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'Jane Worker',
            'department_id' => $dept->id,
            'role' => 'Manager',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_amount' => 700,
            'daily_salary' => 700,
            'status' => 'ACTIVE'
        ]);

        $subAdminEditor = User::create([
            'name' => 'Sub Admin Editor',
            'username' => 'subadmin_editor',
            'phone' => '+91 9898000031',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => [
                'view_attendance_dashboard',
                'edit_attendance_departments',
                'edit_attendance_workers',
                'edit_attendance_daily',
                'edit_attendance_reports'
            ]
        ]);

        // 1. Edit Daily UI has Save button
        $respDaily = $this->withSession(['auth_user' => $subAdminEditor->toArray()])
            ->get('/sub_admin/attendance/daily');
        $respDaily->assertStatus(200);
        $contentDaily = $respDaily->getContent();
        $this->assertStringContainsString('>Save</button>', $contentDaily);
        $this->assertStringContainsString('>Final Save</button>', $contentDaily);

        // 2. Edit Daily save succeeds
        $respDailyPost = $this->withSession(['auth_user' => $subAdminEditor->toArray()])
            ->postJson('/sub_admin/attendance/daily', [
                'date' => now()->toDateString(),
                'save_mode' => 'partial',
                'attendances' => [
                    ['worker_id' => $worker->id, 'status' => 'PRESENT', 'shift_type' => 'DAY']
                ]
            ]);
        $respDailyPost->assertStatus(200);
        $respDailyPost->assertJson(['success' => true]);

        // 3. Edit Department succeeds
        $respDeptPost = $this->withSession(['auth_user' => $subAdminEditor->toArray()])
            ->postJson('/sub_admin/attendance/departments', [
                'name' => 'New Editor Dept'
            ]);
        $respDeptPost->assertStatus(200);
        $this->assertDatabaseHas('departments', ['name' => 'New Editor Dept']);

        // 4. Edit Worker succeeds
        $respWorkerPost = $this->withSession(['auth_user' => $subAdminEditor->toArray()])
            ->postJson('/sub_admin/attendance/workers', [
                'name' => 'New Editor Worker',
                'department_id' => $dept->id,
                'shift_type' => 'DAY',
                'salary_type' => 'DAILY',
                'salary_amount' => 750,
                'status' => 'ACTIVE'
            ]);
        $respWorkerPost->assertStatus(200);
        $this->assertDatabaseHas('workers', ['name' => 'New Editor Worker']);

        // 5. Toggle Paid succeeds
        $respPaidToggle = $this->withSession(['auth_user' => $subAdminEditor->toArray()])
            ->postJson("/sub_admin/attendance/reports/worker/{$worker->id}/toggle-paid", [
                'month' => now()->format('Y-m'),
                'is_paid' => 1
            ]);
        $respPaidToggle->assertStatus(200);
        $respPaidToggle->assertJson(['success' => true, 'is_paid' => true]);
    }
}
