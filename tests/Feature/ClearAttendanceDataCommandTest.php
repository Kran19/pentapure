<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerMonthlyAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearAttendanceDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_attendance_data_command_clears_records_and_preserves_workers_and_departments(): void
    {
        $initialUserCount = User::count();

        $admin = User::create([
            'name' => 'Attendance Admin',
            'email' => 'att_admin@example.com',
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

        // Create attendance record
        Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-09-29',
            'in_time' => '09:00:00',
            'out_time' => '17:00:00',
            'total_hours' => 8,
            'status' => 'PRESENT',
            'calculated_wage' => 500,
        ]);

        // Create submission
        AttendanceSubmission::create([
            'attendance_date' => '2026-09-29',
            'status' => 'SUBMITTED',
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        // Create monthly adjustment
        WorkerMonthlyAdjustment::create([
            'worker_id' => $worker->id,
            'month' => '2026-09',
            'petrol_food_amount' => 100,
            'advance' => 50,
        ]);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_submissions', 1);
        $this->assertDatabaseCount('worker_monthly_adjustments', 1);
        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseCount('departments', 1);

        $this->artisan('attendance:clear-data', ['--force' => true])
            ->assertExitCode(0);

        // Transactional tables are cleared
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('attendance_submissions', 0);
        $this->assertDatabaseCount('worker_monthly_adjustments', 0);

        // Master records are 100% PRESERVED!
        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseCount('departments', 1);
        $this->assertEquals($initialUserCount + 1, User::count());
    }

    public function test_web_route_clears_attendance_data(): void
    {
        $admin = User::create([
            'name' => 'Attendance Super Admin',
            'email' => 'att_super@example.com',
            'password' => 'secret123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $dept = Department::create([
            'name' => 'MUKADAM',
            'is_active' => true,
        ]);

        $worker = Worker::create([
            'name' => 'Suresh Worker',
            'department_id' => $dept->id,
            'salary_type' => 'DAILY',
            'salary_rate' => 450,
            'status' => 'ACTIVE',
        ]);

        Attendance::create([
            'worker_id' => $worker->id,
            'date' => '2026-09-28',
            'status' => 'PRESENT',
            'calculated_wage' => 450,
        ]);

        AttendanceSubmission::create([
            'attendance_date' => '2026-09-28',
            'status' => 'PARTIAL_SAVED',
            'created_by' => $admin->id,
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        $responseBefore = $this->withSession($session)->get('/admin/attendance/dashboard');
        $responseBefore->assertStatus(200);
        $responseBefore->assertSee('PARTIAL SAVED');

        $clearResponse = $this->withSession($session)->post('/admin/attendance/clear');
        $clearResponse->assertRedirect();
        $clearResponse->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('attendance_submissions', 0);
        $this->assertDatabaseCount('workers', 1);

        $responseAfter = $this->withSession($session)->get('/admin/attendance/dashboard');
        $responseAfter->assertStatus(200);
        $responseAfter->assertDontSee('PARTIAL SAVED');
        $responseAfter->assertSee('No recent attendance records found.');
    }
}
