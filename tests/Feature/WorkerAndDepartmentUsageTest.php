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
}
