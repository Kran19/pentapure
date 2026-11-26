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

    public function test_transporter_displays_driver_and_vehicle_added_in_dispatch(): void
    {
        // 1. Create a transporter with empty contact and vehicles (represented as — or null)
        $transporter = Transporter::create([
            'name'     => 'FAST LOGISTICS',
            'gst'      => '24AAAAA0000A1Z5',
            'contact'  => '—',
            'vehicles' => '—',
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

        // 2. Dispatch user creates dispatch with driver contact and vehicle number
        SalesController::ensureVehicleNoColumnExists();

        DispatchLog::create([
            'user_id'        => $this->salesUser->id,
            'order_id'       => $order->id,
            'transporter_id' => $transporter->id,
            'driver_no'      => '+91 9265666204',
            'vehicle_no'     => 'GJ04X9145',
            'lr_no'          => 'LR-12345',
            'notes'          => 'Dispatched successfully',
        ]);

        // 3. SalesController::resolveTransportCompanies() should resolve contact and vehicle
        $transportList = SalesController::resolveTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        $this->assertEquals('+91 9265666204', $matched['contact']);
        $this->assertEquals('GJ04X9145', $matched['vehicles']);

        // 4. In Sales action page, the table should contain the contact and vehicle
        $session = ['auth_user' => [
            'id' => $this->salesUser->id,
            'name' => $this->salesUser->name,
            'role' => 'SALES',
        ]];
        $response = $this->withSession($session)->get(route('sales.action'));
        $response->assertOk();
        $response->assertSee('+91 9265666204');
        $response->assertSee('GJ04X9145');
    }

    public function test_multiple_dispatches_with_different_vehicles_are_combined(): void
    {
        $transporter = Transporter::create([
            'name'     => 'SUPER EXPRESS',
            'gst'      => 'N/A',
            'contact'  => null,
            'vehicles' => null,
        ]);

        $company = Company::create(['name' => 'TEST CO']);

        $order1 = Order::create([
            'created_by'      => $this->salesUser->id,
            'company_id'      => $company->id,
            'transporter_id'  => $transporter->id,
            'total'           => 500,
            'notes'           => 'Note 1',
        ]);

        $order2 = Order::create([
            'created_by'      => $this->salesUser->id,
            'company_id'      => $company->id,
            'transporter_id'  => $transporter->id,
            'total'           => 500,
            'notes'           => 'Note 2',
        ]);

        SalesController::ensureVehicleNoColumnExists();

        DispatchLog::create([
            'user_id'        => $this->salesUser->id,
            'order_id'       => $order1->id,
            'transporter_id' => $transporter->id,
            'driver_no'      => '+91 9825012345',
            'vehicle_no'     => 'GJ01AB1111',
        ]);

        DispatchLog::create([
            'user_id'        => $this->salesUser->id,
            'order_id'       => $order2->id,
            'transporter_id' => $transporter->id,
            'driver_no'      => '+91 9825099999',
            'vehicle_no'     => 'GJ01CD2222',
        ]);

        $transportList = SalesController::resolveTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        // Latest contact
        $this->assertEquals('+91 9825099999', $matched['contact']);
        // Contains both vehicles
        $this->assertStringContainsString('GJ01AB1111', $matched['vehicles']);
        $this->assertStringContainsString('GJ01CD2222', $matched['vehicles']);
    }

    public function test_existing_valid_transporter_data_is_preserved_no_data_loss(): void
    {
        $transporter = Transporter::create([
            'name'     => 'PRESERVED TRANSPORTER',
            'gst'      => '24ZZZZZ9999Z9Z9',
            'contact'  => '+91 9111122222',
            'vehicles' => 'GJ05XY9999',
        ]);

        $transportList = SalesController::resolveTransportCompanies();
        $matched = $transportList->firstWhere('id', $transporter->id);

        $this->assertNotNull($matched);
        $this->assertEquals('+91 9111122222', $matched['contact']);
        $this->assertEquals('GJ05XY9999', $matched['vehicles']);
    }
}
