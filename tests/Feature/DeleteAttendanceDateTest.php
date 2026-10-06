<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAttendanceDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_attendance_for_specific_date(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $dept = Department::create([
            'name' => 'PRODUCTION DEPT',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name' => 'Ramesh Kumar',
            'department_id' => $dept->id,
            'role' => 'Operator',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY',
            'salary_rate' => 500,
            'status' => 'ACTIVE',
        ]);

        // Create 2026-10-01 records
        Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-10-01',
            'status' => 'PRESENT',
            'total_hours' => 8,
            'calculated_wage' => 500,
        ]);

        AttendanceSubmission::create([
            'attendance_date' => '2026-10-01',
            'status' => 'SUBMITTED',
            'created_by' => $admin->id,
        ]);

        // Create 2026-10-02 records (should not be deleted)
        Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-10-02',
            'status' => 'PRESENT',
            'total_hours' => 8,
            'calculated_wage' => 500,
        ]);

        AttendanceSubmission::create([
            'attendance_date' => '2026-10-02',
            'status' => 'SUBMITTED',
            'created_by' => $admin->id,
        ]);

        $this->assertEquals(1, AttendanceSubmission::whereDate('attendance_date', '2026-10-01')->count());
        $this->assertEquals(1, Attendance::whereDate('date', '2026-10-01')->count());

        $response = $this->withSession(['auth_user' => [
            'id' => $admin->id,
            'name' => $admin->name,
            'role' => 'ADMIN',
        ]])->postJson('/admin/attendance/daily/delete', [
            'date' => '2026-10-01'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 2026-10-01 should be missing
        $this->assertEquals(0, AttendanceSubmission::whereDate('attendance_date', '2026-10-01')->count());
        $this->assertEquals(0, Attendance::whereDate('date', '2026-10-01')->count());

        // 2026-10-02 should still exist
        $this->assertEquals(1, AttendanceSubmission::whereDate('attendance_date', '2026-10-02')->count());
        $this->assertEquals(1, Attendance::whereDate('date', '2026-10-02')->count());
    }
}
