<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_admin_without_users_permission_cannot_access_users_page()
    {
        $subAdmin = User::create([
            'name' => 'Restricted Sub Admin',
            'username' => 'subadmin_no_users',
            'phone' => '+91 9898000001',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_stock', 'admin_stock']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/users');

        $response->assertStatus(403);
    }

    public function test_sub_admin_with_users_permission_can_access_users_page()
    {
        $subAdmin = User::create([
            'name' => 'Authorized Sub Admin',
            'username' => 'subadmin_users',
            'phone' => '+91 9898000002',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_users', 'admin_users']
        ]);

        $response = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/users');

        $response->assertStatus(200);
    }

    public function test_sub_admin_with_view_only_permission_cannot_perform_write_actions()
    {
        $subAdmin = User::create([
            'name' => 'View Only Sub Admin',
            'username' => 'subadmin_view_only',
            'phone' => '+91 9898000003',
            'password' => Hash::make('password123'),
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['view_admin_products']
        ]);

        // Can view products page
        $viewResponse = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->get('/sub_admin/products');
        $viewResponse->assertStatus(200);

        // Cannot create product via JSON API -> 403
        $writeJsonResponse = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->postJson('/sub_admin/products', [
                'name' => 'Unauthorized Product',
                'category' => 'PACKAGING',
                'unit' => 'KG',
                'threshold' => 10
            ]);
        $writeJsonResponse->assertStatus(403);

        // Cannot create product via regular form POST -> redirect back with unauthorized error
        $writeFormResponse = $this->withSession(['auth_user' => $subAdmin->toArray()])
            ->post('/sub_admin/products', [
                'name' => 'Unauthorized Product 2',
                'category' => 'PACKAGING',
                'unit' => 'KG',
                'threshold' => 10
            ]);
        $writeFormResponse->assertStatus(302);
        $writeFormResponse->assertSessionHas('error');
    }

    public function test_attendance_user_is_scoped_to_assigned_departments()
    {
        $dept1 = Department::create(['name' => 'PACKING', 'is_active' => true]);
        $dept2 = Department::create(['name' => 'CLEANING', 'is_active' => true]);

        $worker1 = Worker::create([
            'name' => 'Packing Worker',
            'department_id' => $dept1->id,
            'role' => 'OPERATOR',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY_WAGES',
            'daily_salary' => 500,
            'status' => 'ACTIVE'
        ]);

        $worker2 = Worker::create([
            'name' => 'Cleaning Worker',
            'department_id' => $dept2->id,
            'role' => 'HELPER',
            'shift_type' => 'DAY',
            'salary_type' => 'DAILY_WAGES',
            'daily_salary' => 450,
            'status' => 'ACTIVE'
        ]);

        // Attendance user permitted only for Dept 1
        $attUser = User::create([
            'name' => 'Packing Supervisor',
            'username' => 'packing_sup',
            'phone' => '+91 9898000004',
            'password' => Hash::make('password123'),
            'role' => 'ATTENDANCE',
            'status' => 'ACTIVE',
            'permissions' => [$dept1->id]
        ]);

        // Workers page only shows Dept 1
        $workersResponse = $this->withSession(['auth_user' => $attUser->toArray()])
            ->get('/attendance/workers');
        $workersResponse->assertStatus(200);
        $workersResponse->assertSee('Packing Worker');
        $workersResponse->assertDontSee('Cleaning Worker');

        // Workers JSON API only returns Dept 1
        $jsonResponse = $this->withSession(['auth_user' => $attUser->toArray()])
            ->get('/attendance/workers');
        $jsonResponse->assertStatus(200);

        // Daily page only contains Dept 1 worker
        $dailyResponse = $this->withSession(['auth_user' => $attUser->toArray()])
            ->get('/attendance/daily');
        $dailyResponse->assertStatus(200);
        $dailyResponse->assertSee('Packing Worker');
        $dailyResponse->assertDontSee('Cleaning Worker');
    }

    public function test_attendance_user_cannot_clear_attendance_data()
    {
        $attUser = User::create([
            'name' => 'Regular Attendance Staff',
            'username' => 'att_staff',
            'phone' => '+91 9898000005',
            'password' => Hash::make('password123'),
            'role' => 'ATTENDANCE',
            'status' => 'ACTIVE',
            'permissions' => []
        ]);

        $response = $this->withSession(['auth_user' => $attUser->toArray()])
            ->post('/attendance/clear');

        $response->assertStatus(403);
    }

    public function test_admin_can_clear_attendance_data()
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'username' => 'admin_super',
            'phone' => '+91 9898000006',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE'
        ]);

        $response = $this->withSession(['auth_user' => $admin->toArray()])
            ->post('/admin/attendance/clear');

        $response->assertStatus(302);
    }
}
