<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceSaveModeTest extends TestCase
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

    public function test_partial_save_succeeds_without_out_times_and_sets_status_partial_saved(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $session = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $dept = Department::create(['name' => 'PRODUCTION']);
        $worker = Worker::create([
            'name'          => 'Regular Worker',
            'department_id' => $dept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-10-12',
            'save_mode'   => 'partial',
            'attendances' => [
                [
                    'worker_id'   => $worker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'in_time'     => '09:10 AM',
                    'out_time'    => null,
                    'ot_ut'       => 'NONE',
                    'ot_ut_hours' => 0,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('PARTIAL_SAVED', \App\Models\AttendanceSubmission::first()->status);
    }

    public function test_final_save_succeeds_for_mukadam_and_exempt_statuses_without_out_times(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $session = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $mukadamDept = Department::create(['name' => 'MUKADAM']);
        $mukadamWorker = Worker::create([
            'name'          => 'Bhilbhil Dhudh',
            'department_id' => $mukadamDept->id,
            'role'          => 'Mukadam',
            'shift_type'    => 'DAY',
            'salary_type'   => 'LABOUR_MUKADAM',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $prodDept = Department::create(['name' => 'PRODUCTION']);
        $sundayWorker = Worker::create([
            'name'          => 'Sunday Worker',
            'department_id' => $prodDept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $regularWorker = Worker::create([
            'name'          => 'Regular Worker',
            'department_id' => $prodDept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-10-12',
            'save_mode'   => 'final',
            'attendances' => [
                [
                    'worker_id'   => $mukadamWorker->id,
                    'status'      => 'PRESENT',
                    'num_workers' => 25,
                    'shift_type'  => 'DAY',
                    'in_time'     => '09:10 AM',
                    'out_time'    => null, // Mukadam contractor does not require out time
                ],
                [
                    'worker_id'   => $sundayWorker->id,
                    'status'      => 'SUNDAY',
                    'in_time'     => null,
                    'out_time'    => null,
                ],
                [
                    'worker_id'   => $regularWorker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'in_time'     => '09:00 AM',
                    'out_time'    => '06:00 PM',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('SUBMITTED', \App\Models\AttendanceSubmission::first()->status);
    }

    public function test_final_save_rejects_regular_worker_missing_out_time_with_worker_name(): void
    {
        $attendanceUser = $this->createUser('ATTENDANCE');
        $session = ['auth_user' => ['id' => $attendanceUser->id, 'name' => $attendanceUser->name, 'role' => 'ATTENDANCE']];

        $prodDept = Department::create(['name' => 'PRODUCTION']);
        $regularWorker = Worker::create([
            'name'          => 'Aakash Verma',
            'department_id' => $prodDept->id,
            'role'          => 'Operator',
            'shift_type'    => 'DAY',
            'salary_type'   => 'DAILY',
            'daily_salary'  => 500,
            'status'        => 'ACTIVE',
        ]);

        $response = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-10-12',
            'save_mode'   => 'final',
            'attendances' => [
                [
                    'worker_id'   => $regularWorker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'in_time'     => '09:00 AM',
                    'out_time'    => null,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('Aakash Verma', $response->json('message'));
    }
}
