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
}
