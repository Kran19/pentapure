<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MukadamDecimalWorkersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $attendanceUser;
    protected Department $mukadamDept;
    protected Worker $mukadamWorker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin User',
            'username' => 'admin_mukadam_dec',
            'email'    => 'admin_mukadam_dec@pentapure.com',
            'password' => 'admin@123',
            'role'     => 'ADMIN',
            'status'   => 'ACTIVE',
        ]);

        $this->attendanceUser = User::create([
            'name'     => 'Attendance User',
            'username' => 'att_mukadam_dec',
            'email'    => 'att_mukadam_dec@pentapure.com',
            'password' => 'att@123',
            'role'     => 'ATTENDANCE',
            'status'   => 'ACTIVE',
        ]);

        $this->mukadamDept = Department::create(['name' => 'MUKADAM']);

        $this->mukadamWorker = Worker::create([
            'name'            => 'AHIR LALALBHAI',
            'department_id'   => $this->mukadamDept->id,
            'role'            => 'Mukadam',
            'shift_type'      => 'DAY',
            'salary_type'     => 'LABOUR_MUKADAM',
            'salary_amount'   => 500,
            'daily_salary'    => 500,
            'per_hour_salary' => 50,
            'status'          => 'ACTIVE',
        ]);
    }

    public function test_daily_entry_form_allows_decimal_step_for_mukadam_num_workers(): void
    {
        $res = $this->withSession(['auth_user' => $this->attendanceUser->toArray()])
            ->get('/attendance/daily?date=2026-11-01');

        $res->assertStatus(200);
        $res->assertSee('step="any"', false);
        $res->assertSee('inputmode="decimal"', false);
        $res->assertSee('num-workers-input', false);
    }

    public function test_mukadam_decimal_worker_counts_save_and_calculate_monthly_payroll_accurately(): void
    {
        $session = ['auth_user' => $this->attendanceUser->toArray()];

        // Day 1: 0.5 workers (partial save)
        $res1 = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-11-01',
            'save_mode'   => 'partial',
            'attendances' => [
                [
                    'worker_id'   => $this->mukadamWorker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'num_workers' => '0.5',
                    'ot_ut'       => 'NONE',
                    'ot_ut_hours' => 0,
                    'advance'     => 0,
                    'remark'      => 'Half labour',
                ],
            ],
        ]);
        $res1->assertStatus(200)->assertJson(['success' => true]);

        // Day 2: 0.7 workers (final save)
        $res2 = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-11-02',
            'save_mode'   => 'final',
            'attendances' => [
                [
                    'worker_id'   => $this->mukadamWorker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'num_workers' => '0.7',
                    'ot_ut'       => 'NONE',
                    'ot_ut_hours' => 0,
                    'advance'     => 0,
                    'remark'      => '0.7 labour',
                ],
            ],
        ]);
        $res2->assertStatus(200)->assertJson(['success' => true]);

        // Day 3: 1.5 workers (final save)
        $res3 = $this->withSession($session)->postJson('/attendance/daily', [
            'date'        => '2026-11-03',
            'save_mode'   => 'final',
            'attendances' => [
                [
                    'worker_id'   => $this->mukadamWorker->id,
                    'status'      => 'PRESENT',
                    'shift_type'  => 'DAY',
                    'num_workers' => 1.5,
                    'ot_ut'       => 'NONE',
                    'ot_ut_hours' => 0,
                    'advance'     => 100,
                    'remark'      => '1.5 labours',
                ],
            ],
        ]);
        $res3->assertStatus(200)->assertJson(['success' => true]);

        // Verify persisted decimal values & calculated wages
        $att1 = Attendance::where('worker_id', $this->mukadamWorker->id)->where('date', '2026-11-01')->firstOrFail();
        $att2 = Attendance::where('worker_id', $this->mukadamWorker->id)->where('date', '2026-11-02')->firstOrFail();
        $att3 = Attendance::where('worker_id', $this->mukadamWorker->id)->where('date', '2026-11-03')->firstOrFail();

        $this->assertSame(0.5, $att1->num_workers);
        $this->assertEquals(250.0, (float) $att1->calculated_wage);

        $this->assertSame(0.7, $att2->num_workers);
        $this->assertEquals(350.0, (float) $att2->calculated_wage);

        $this->assertSame(1.5, $att3->num_workers);
        $this->assertEquals(750.0, (float) $att3->calculated_wage);

        // Reload daily entry page for Day 1 and confirm value="0.5" renders cleanly
        $reloadDay1 = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get('/admin/attendance/daily?date=2026-11-01');
        $reloadDay1->assertStatus(200);
        $reloadDay1->assertSee('value="0.5"', false);

        // Monthly Reports page: 0.5 + 0.7 + 1.5 = 2.7 labours -> Earned: 2.7 * 500 = ₹1,350; Payable: 1350 - 100 = ₹1,250.00
        $reportsRes = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get('/admin/attendance/reports?month=2026-11');
        $reportsRes->assertStatus(200);
        $reportsRes->assertSee('2.7');
        $reportsRes->assertSee('Earned: ₹1,350');
        $reportsRes->assertSee('₹1,250.00');

        // Individual Worker Report Sheet
        $workerSheetRes = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get("/admin/attendance/reports/worker/{$this->mukadamWorker->id}?month=2026-11");
        $workerSheetRes->assertStatus(200);
        $workerSheetRes->assertSee('0.5');
        $workerSheetRes->assertSee('0.7');
        $workerSheetRes->assertSee('1.5');
        $workerSheetRes->assertSee('2.7');
        $workerSheetRes->assertSee('1,350.00');

        // PDF endpoints succeed cleanly
        $dailyPdfRes = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get('/admin/attendance/daily/pdf?date=2026-11-03');
        $dailyPdfRes->assertStatus(200);

        $summaryPdfRes = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get('/admin/attendance/reports/summary/pdf?month=2026-11');
        $summaryPdfRes->assertStatus(200);

        $workerPdfRes = $this->withSession(['auth_user' => $this->admin->toArray()])
            ->get("/admin/attendance/reports/worker/{$this->mukadamWorker->id}/pdf?month=2026-11");
        $workerPdfRes->assertStatus(200);
    }
}
