<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerMonthlyAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkerAndDepartmentUsageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'Admin User',
            'username' => 'admin_user',
            'phone'    => '+91 9999999999',
            'password' => Hash::make('password123'),
            'role'     => 'ADMIN',
            'status'   => 'ACTIVE',
        ]);
    }

    public function test_cannot_delete_department_with_assigned_workers(): void
    {
        $dept = Department::create([
            'name'      => 'Packing Section',
            'is_active' => true,
        ]);

        Worker::create([
            'name'          => 'Worker One',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 500,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/departments/{$dept->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('in use by 1 worker(s)', $response->json('message'));
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_cannot_delete_department_with_attendance_records(): void
    {
        $dept = Department::create([
            'name'      => 'Boiler Section',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Boiler Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 600,
            'status'        => 'ACTIVE',
        ]);

        Attendance::create([
            'worker_id' => $worker->id,
            'date'      => now()->toDateString(),
            'status'    => 'PRESENT',
            'in_time'   => '09:00',
            'out_time'  => '18:00',
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/departments/{$dept->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_cannot_delete_department_with_monthly_adjustments(): void
    {
        $dept = Department::create([
            'name'      => 'Maintenance Section',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Maintenance Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'MONTHLY',
            'salary_amount' => 15000,
            'status'        => 'ACTIVE',
        ]);

        WorkerMonthlyAdjustment::create([
            'worker_id' => $worker->id,
            'month'     => now()->format('Y-m'),
            'advance'   => 1000,
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/departments/{$dept->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_cannot_delete_system_department_mukadam(): void
    {
        $dept = Department::firstOrCreate(['name' => 'MUKADAM'], ['is_active' => true]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/departments/{$dept->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('fixed system department', $response->json('message'));
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_can_delete_unused_department(): void
    {
        $dept = Department::create([
            'name'      => 'Temporary Empty Dept',
            'is_active' => true,
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/departments/{$dept->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Department deleted',
        ]);
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_cannot_delete_worker_with_attendance_records(): void
    {
        $dept = Department::create([
            'name'      => 'Testing Dept',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Attendance Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 500,
            'status'        => 'ACTIVE',
        ]);

        Attendance::create([
            'worker_id' => $worker->id,
            'date'      => now()->toDateString(),
            'status'    => 'PRESENT',
            'in_time'   => '08:00',
            'out_time'  => '17:00',
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/workers/{$worker->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('attendance record(s) in use', $response->json('message'));
        $this->assertStringContainsString('INACTIVE', $response->json('message'));
        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_cannot_delete_worker_with_monthly_salary_adjustments(): void
    {
        $dept = Department::create([
            'name'      => 'Testing Dept 2',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Salary Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 450,
            'status'        => 'ACTIVE',
        ]);

        WorkerMonthlyAdjustment::create([
            'worker_id' => $worker->id,
            'month'     => now()->format('Y-m'),
            'advance'   => 500,
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/workers/{$worker->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('salary adjustment record(s) in use', $response->json('message'));
        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_can_delete_unused_worker(): void
    {
        $dept = Department::create([
            'name'      => 'Testing Dept 3',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Unused Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 400,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->deleteJson("/admin/attendance/workers/{$worker->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Worker deleted',
        ]);
        $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
    }

    public function test_can_toggle_department_status_to_disabled_and_enabled(): void
    {
        $dept = Department::create([
            'name'      => 'Disablable Dept',
            'is_active' => true,
        ]);

        // Toggle to disabled (Inactive)
        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->postJson("/admin/attendance/departments/{$dept->id}/toggle-status");

        $response->assertStatus(200);
        $response->assertJson([
            'success'   => true,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('departments', [
            'id'        => $dept->id,
            'is_active' => false,
        ]);

        // Toggle back to enabled (Active)
        $response2 = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->postJson("/admin/attendance/departments/{$dept->id}/toggle-status");

        $response2->assertStatus(200);
        $response2->assertJson([
            'success'   => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('departments', [
            'id'        => $dept->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_disable_mukadam_department(): void
    {
        $dept = Department::firstOrCreate(['name' => 'MUKADAM'], ['is_active' => true]);

        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->postJson("/admin/attendance/departments/{$dept->id}/toggle-status");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('departments', [
            'id'        => $dept->id,
            'is_active' => true,
        ]);
    }

    public function test_can_toggle_worker_status_to_disabled_and_enabled(): void
    {
        $dept = Department::create([
            'name'      => 'Worker Status Dept',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name'          => 'Toggleable Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 450,
            'status'        => 'ACTIVE',
        ]);

        // Toggle to disabled (INACTIVE)
        $response = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->postJson("/admin/attendance/workers/{$worker->id}/toggle-status");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'INACTIVE',
        ]);
        $this->assertDatabaseHas('workers', [
            'id'     => $worker->id,
            'status' => 'INACTIVE',
        ]);

        // Toggle back to enabled (ACTIVE)
        $response2 = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->postJson("/admin/attendance/workers/{$worker->id}/toggle-status");

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'status'  => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('workers', [
            'id'     => $worker->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_departments_page_shows_disabled_delete_button_for_used_department_and_enabled_for_unused(): void
    {
        // 1. Used department (with worker)
        $usedDept = Department::create([
            'name'      => 'Used Section',
            'is_active' => true,
        ]);
        Worker::create([
            'name'          => 'Worker in Used Dept',
            'department_id' => $usedDept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 500,
            'status'        => 'ACTIVE',
        ]);

        // 2. Unused department
        $unusedDept = Department::create([
            'name'      => 'Unused Section',
            'is_active' => true,
        ]);

        // Test as Admin
        $responseAdmin = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->get('/admin/attendance/departments');

        $responseAdmin->assertStatus(200);
        // Verify disabled button for used department
        $responseAdmin->assertSee('Cannot delete: department is in use');
        // Verify active delete button for unused department
        $responseAdmin->assertSee("deleteDept({$unusedDept->id})");

        // Test as Sub-Admin
        $subAdmin = User::create([
            'name'        => 'Sub Admin User',
            'username'    => 'subadmin_att',
            'phone'       => '+91 9999990001',
            'password'    => Hash::make('password123'),
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['attendance_departments', 'edit_attendance_departments', 'attendance_workers', 'edit_attendance_workers']
        ]);

        $responseSubAdmin = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/attendance/departments');

        $responseSubAdmin->assertStatus(200);
        $responseSubAdmin->assertSee('Cannot delete: department is in use');
        $responseSubAdmin->assertSee("deleteDept({$unusedDept->id})");
    }

    public function test_workers_page_shows_disabled_delete_button_for_used_worker_and_enabled_for_unused(): void
    {
        $dept = Department::create([
            'name'      => 'General Dept',
            'is_active' => true,
        ]);

        // 1. Used worker (has attendance)
        $usedWorker = Worker::create([
            'name'          => 'Used Worker Attendance',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 500,
            'status'        => 'ACTIVE',
        ]);
        Attendance::create([
            'worker_id' => $usedWorker->id,
            'date'      => now()->toDateString(),
            'status'    => 'PRESENT',
            'in_time'   => '09:00',
            'out_time'  => '18:00',
        ]);

        // 2. Unused worker
        $unusedWorker = Worker::create([
            'name'          => 'Unused Fresh Worker',
            'department_id' => $dept->id,
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'salary_amount' => 450,
            'status'        => 'ACTIVE',
        ]);

        // Test as Admin
        $responseAdmin = $this->withSession(['auth_user' => $this->adminUser->toArray()])
            ->get('/admin/attendance/workers');

        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Cannot delete: worker has attendance or salary records in use');
        $responseAdmin->assertSee("deleteWorker({$unusedWorker->id})");

        // Test as Sub-Admin
        $subAdmin = User::create([
            'name'        => 'Sub Admin User 2',
            'username'    => 'subadmin_att_2',
            'phone'       => '+91 9999990002',
            'password'    => Hash::make('password123'),
            'role'        => 'SUB_ADMIN',
            'status'      => 'ACTIVE',
            'permissions' => ['attendance_departments', 'edit_attendance_departments', 'attendance_workers', 'edit_attendance_workers']
        ]);

        $responseSubAdmin = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/attendance/workers');

        $responseSubAdmin->assertStatus(200);
        $responseSubAdmin->assertSee('Cannot delete: worker has attendance or salary records in use');
        $responseSubAdmin->assertSee("deleteWorker({$unusedWorker->id})");
    }
}
