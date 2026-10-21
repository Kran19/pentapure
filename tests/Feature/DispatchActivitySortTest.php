<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchActivitySortTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin_sort_test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_dispatch_activity_sorts_pending_first_with_more_due_days_on_top(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];
        $company = Company::create(['name' => 'Sort Test Co']);

        // 1. Fully completed order
        $orderDone = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 1000,
            'status' => 'APPROVED',
            'dispatch_status' => 'DONE',
            'date' => '2026-09-30',
            'due_date' => '2026-09-20',
        ]);

        // 2. Partial order
        $orderPartial = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 2000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PARTIAL',
            'date' => '2026-09-28',
            'due_date' => '2026-09-25',
        ]);

        // 3. Pending order due 2 days ago (less overdue)
        $orderPendingRecentDue = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 3000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'date' => '2026-09-26',
            'due_date' => '2026-09-28',
        ]);

        // 4. Pending order due 10 days ago (more overdue / more due days -> MUST BE TOP)
        $orderPendingOldDue = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 4000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'date' => '2026-09-18',
            'due_date' => '2026-09-20',
        ]);

        $response = $this->withSession($session)->get('/admin/dispatch-activity');
        $response->assertStatus(200);

        $orders = $response->viewData('pageData')['orders'];
        $orderIds = $orders->pluck('id')->toArray();

        // Check order of IDs:
        // #1: $orderPendingOldDue (Pending, due 2026-09-20 - most overdue)
        // #2: $orderPendingRecentDue (Pending, due 2026-09-28)
        // #3: $orderPartial (Partial)
        // #4: $orderDone (Done / Completed)
        $this->assertEquals([
            $orderPendingOldDue->id,
            $orderPendingRecentDue->id,
            $orderPartial->id,
            $orderDone->id,
        ], $orderIds);
    }

    public function test_orders_without_expected_delivery_date_show_in_last_of_active_orders(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];
        $company = Company::create(['name' => 'Sort Test Co 2']);

        // 1. Pending order WITH due date (due 2026-09-20)
        $orderPendingWithDue = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 1000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'date' => '2026-09-21',
            'due_date' => '2026-09-20',
        ]);

        // 2. Partial order WITH due date (due 2026-09-25)
        $orderPartialWithDue = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 2000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PARTIAL',
            'date' => '2026-09-21',
            'due_date' => '2026-09-25',
        ]);

        // 3. Pending order with NO expected delivery date (null due_date)
        $orderPendingNoDue = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 3000,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'date' => '2026-09-10', // Ordered early, but no due date specified
            'due_date' => null,
        ]);

        // 4. Completed order (DONE)
        $orderDone = Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 4000,
            'status' => 'APPROVED',
            'dispatch_status' => 'DONE',
            'date' => '2026-09-30',
            'due_date' => '2026-09-20',
        ]);

        // In ALL tab:
        // Order must be:
        // #1: $orderPendingWithDue (Pending with due date)
        // #2: $orderPartialWithDue (Partial with due date)
        // #3: $orderPendingNoDue (Pending with NO due date - shown in last of active orders)
        // #4: $orderDone (Done / Completed)
        $response = $this->withSession($session)->get('/admin/dispatch-activity');
        $response->assertStatus(200);

        $orders = $response->viewData('pageData')['orders'];
        $orderIds = $orders->pluck('id')->toArray();

        $this->assertEquals([
            $orderPendingWithDue->id,
            $orderPartialWithDue->id,
            $orderPendingNoDue->id,
            $orderDone->id,
        ], $orderIds);

        // In PENDING tab:
        // #1: $orderPendingWithDue
        // #2: $orderPendingNoDue (in last)
        $resPending = $this->withSession($session)->get('/admin/dispatch-activity?status=PENDING');
        $resPending->assertStatus(200);

        $pendingOrderIds = $resPending->viewData('pageData')['orders']->pluck('id')->toArray();
        $this->assertEquals([
            $orderPendingWithDue->id,
            $orderPendingNoDue->id,
        ], $pendingOrderIds);
    }

    public function test_dispatch_activity_pdf_exports_successfully(): void
    {
        $admin = $this->createAdmin();
        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];
        $company = Company::create(['name' => 'PDF Test Co']);

        Order::create([
            'created_by' => $admin->id,
            'company_id' => $company->id,
            'total' => 1200,
            'status' => 'APPROVED',
            'dispatch_status' => 'PENDING',
            'due_date' => '2026-09-20',
        ]);

        $response = $this->withSession($session)->get('/admin/dispatch-activity/pdf');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
