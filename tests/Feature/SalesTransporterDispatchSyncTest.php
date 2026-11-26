<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Transporter;
use App\Models\Order;
use App\Models\DispatchLog;
use App\Http\Controllers\SalesController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesTransporterDispatchSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesUser = User::create([
            'name'     => 'Sales Manager',
            'email'    => 'sales@example.com',
            'password' => 'secret123',
            'role'     => 'SALES',
            'status'   => 'ACTIVE',
        ]);
        $this->actingAs($this->salesUser);
    }

    public function test_dispatch_stores_data_in_dispatch_logs_and_not_in_transporters_master_table(): void
    {
        // 1. Create a transporter with no vehicles or contact
        $transporter = Transporter::create([
            'name'     => 'FAST LOGISTICS',
            'gst'      => '24AAAAA0000A1Z5',
            'contact'  => null,
            'vehicles' => null,
        ]);

        $company = Company::create([
            'name' => 'ACME CORP',
            'gst'  => '24BBBBB1111B1Z6',
        ]);

        $order = Order::create([
            'created_by'      => $this->salesUser->id,
            'company_id'      => $company->id,
            'transporter_id'  => $transporter->id,
            'total'           => 1000,
            'status'          => 'OPEN',
            'dispatch_status' => 'PENDING',
            'notes'           => 'Urgent dispatch',
        ]);

        SalesController::ensureVehicleNoColumnExists();

        // Dispatch user enters driver contact, vehicle number / note
        $dispatchLog = DispatchLog::create([
            'user_id'        => $this->salesUser->id,
            'order_id'       => $order->id,
            'transporter_id' => $transporter->id,
            'driver_no'      => '+91 9265666204',
            'vehicle_no'     => 'ACTUAL DISPATCH ON 20-11-2026(DUE TO APP.PROBLEM)',
            'lr_no'          => 'LR-12345',
            'notes'          => 'Dispatched successfully',
        ]);

        // Dispatch log must have the dispatch entry
        $this->assertEquals('ACTUAL DISPATCH ON 20-11-2026(DUE TO APP.PROBLEM)', $dispatchLog->vehicle_no);
        $this->assertEquals('+91 9265666204', $dispatchLog->driver_no);

        // Transporter master record must NOT be polluted
        $freshTrans = $transporter->fresh();
        $this->assertNull($freshTrans->vehicles);

        // Transporter list in Sales action must show '—', not dispatch user's note
        $transportList = SalesController::getTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        $this->assertEquals('—', $matched['vehicles']);

        // In Sales action page, the table should NOT contain the dispatch note in vehicles
        $session = ['auth_user' => [
            'id' => $this->salesUser->id,
            'name' => $this->salesUser->name,
            'role' => 'SALES',
        ]];
        $response = $this->withSession($session)->get(route('sales.action'));
        $response->assertOk();
        $response->assertDontSee('ACTUAL DISPATCH ON 20-11-2026(DUE TO APP.PROBLEM)');
    }

    public function test_contaminated_transporter_vehicles_are_cleaned_automatically(): void
    {
        $transporter = Transporter::create([
            'name'     => 'JAI TRANSPORT SERVICE',
            'gst'      => 'N/A',
            'contact'  => null,
            'vehicles' => 'ACTUAL DISPATCH ON 20-11-2026(DUE TO APP.PROBLEM)',
        ]);

        // When loading transport companies, contaminated dispatch notes are cleaned
        $transportList = SalesController::getTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        $this->assertEquals('—', $matched['vehicles']);

        $fresh = $transporter->fresh();
        $this->assertNull($fresh->vehicles);
    }

    public function test_existing_valid_transporter_data_is_preserved_no_data_loss(): void
    {
        $transporter = Transporter::create([
            'name'     => 'PRESERVED TRANSPORTER',
            'gst'      => '24ZZZZZ9999Z9Z9',
            'contact'  => '+91 9111122222',
            'vehicles' => 'GJ05XY9999',
        ]);

        $transportList = SalesController::getTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        $this->assertEquals('+91 9111122222', $matched['contact']);
        $this->assertEquals('GJ05XY9999', $matched['vehicles']);
    }
}
