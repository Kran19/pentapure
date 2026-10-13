<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\Worker;
use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceDailyPdfRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_attendance_daily_pdf_urls_work_for_authenticated_users()
    {
        $admin = User::factory()->create([
            'role'   => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $attendanceUser = User::factory()->create([
            'role'   => 'ATTENDANCE',
            'status' => 'ACTIVE',
        ]);

        $subAdmin = User::factory()->create([
            'role'   => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['attendance_daily'],
        ]);

        $dept = Department::create(['name' => 'FACTORY', 'is_active' => true]);
        $worker = Worker::create([
            'name' => 'Test Worker',
            'worker_code' => 'W101',
            'department_id' => $dept->id,
            'salary_type' => 'DAILY',
            'status' => 'ACTIVE',
        ]);

        $date = '2026-10-12';
        Attendance::create([
            'worker_id' => $worker->id,
            'date' => $date,
            'status' => 'PRESENT',
            'shift' => 'DAY',
        ]);

        AttendanceSubmission::create([
            'attendance_date' => $date,
            'status' => 'SUBMITTED',
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $sessionAttendance = [
            'auth_user' => [
                'id' => $attendanceUser->id,
                'name' => $attendanceUser->name,
                'role' => 'ATTENDANCE',
                'permissions' => [],
            ]
        ];

        $sessionAdmin = [
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'role' => 'ADMIN',
                'permissions' => [],
            ]
        ];

        $sessionSubAdmin = [
            'auth_user' => [
                'id' => $subAdmin->id,
                'name' => $subAdmin->name,
                'role' => 'SUB_ADMIN',
                'permissions' => ['attendance_daily'],
            ]
        ];

        // 1. The exact URL reported by user with doubled prefix
        $res1 = $this->withSession($sessionAttendance)->get("/attendance/attendance/daily/pdf?date={$date}");
        $res1->assertStatus(200);

        // 2. The standard attendance daily PDF URL
        $res2 = $this->withSession($sessionAttendance)->get("/attendance/daily/pdf?date={$date}");
        $res2->assertStatus(200);

        // 3. Admin accessing the doubled URL also works
        $res3 = $this->withSession($sessionAdmin)->get("/attendance/attendance/daily/pdf?date={$date}");
        $res3->assertStatus(200);

        // 4. Admin attendance daily PDF URL
        $res4 = $this->withSession($sessionAdmin)->get("/admin/attendance/daily/pdf?date={$date}");
        $res4->assertStatus(200);

        // 5. Sub-admin attendance daily PDF URL
        $res5 = $this->withSession($sessionSubAdmin)->get("/sub_admin/attendance/daily/pdf?date={$date}");
        $res5->assertStatus(200);
    }
}
