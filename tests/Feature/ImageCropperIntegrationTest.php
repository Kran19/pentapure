<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCropperIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $dispatchUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->dispatchUser = User::create([
            'name' => 'Dispatch User',
            'email' => 'dispatch@example.com',
            'password' => 'password123',
            'role' => 'DISPATCH',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_cashier_action_view_has_camera_and_cropper_components(): void
    {
        $session = ['auth_user' => [
            'id' => $this->cashier->id,
            'name' => $this->cashier->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($session)->get('/cashier/action');
        $response->assertStatus(200);
        $response->assertSee('btn-bill-camera');
        $response->assertSee('tx-bill-camera');
        $response->assertSee('image-cropper.js');
        $response->assertSee('cropper.min.js');
    }

    public function test_dispatch_views_have_camera_and_cropper_components(): void
    {
        $session = ['auth_user' => [
            'id' => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $actionRes = $this->withSession($session)->get('/dispatch/action');
        $actionRes->assertStatus(200);
        $actionRes->assertSee('dispatch-lr-cam');
        $actionRes->assertSee('lr-preview');
        $actionRes->assertSee('image-cropper.js');

        $company = Company::create(['name' => 'Acme Foods']);
        $order = Order::create([
            'company_id' => $company->id,
            'created_by' => $this->dispatchUser->id,
            'total' => 1000,
            'status' => 'PENDING',
        ]);
        \App\Models\DispatchLog::create([
            'order_id' => $order->id,
            'user_id' => $this->dispatchUser->id,
        ]);

        $historyRes = $this->withSession($session)->get('/dispatch/history');
        $historyRes->assertStatus(200);
        $historyRes->assertSee('image-cropper.js');
        $historyRes->assertSee('late-lr-cam');
    }

    public function test_cashier_can_save_transaction_with_cropped_bill_file(): void
    {
        Storage::fake('public');

        $session = ['auth_user' => [
            'id' => $this->cashier->id,
            'name' => $this->cashier->name,
            'role' => 'CASHIER',
        ]];

        $file = UploadedFile::fake()->image('bill_cropped.jpg', 600, 400);

        $response = $this->withSession($session)->post('/cashier/action', [
            'transactions' => [
                [
                    'type' => 'OUT',
                    'amount' => 350.50,
                    'category' => 'office_supplies',
                    'note' => 'Testing cropped bill upload',
                    'reference' => 'BILL-101',
                    'date' => '2026-09-30',
                ]
            ],
            'bills' => [
                0 => $file
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 350.50,
            'reference' => 'BILL-101',
        ]);

        $this->assertDatabaseHas('transaction_bills', [
            'original_name' => 'bill_cropped.jpg',
        ]);
    }

    public function test_dispatch_can_update_lr_with_cropped_image(): void
    {
        $session = ['auth_user' => [
            'id' => $this->dispatchUser->id,
            'name' => $this->dispatchUser->name,
            'role' => 'DISPATCH',
        ]];

        $company = Company::create(['name' => 'Acme Foods']);
        $order = Order::create([
            'company_id' => $company->id,
            'created_by' => $this->dispatchUser->id,
            'total' => 1000,
            'status' => 'PENDING',
        ]);
        $log = \App\Models\DispatchLog::create([
            'order_id' => $order->id,
            'user_id' => $this->dispatchUser->id,
        ]);

        // 1x1 transparent PNG base64
        $fakeBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->withSession($session)->postJson('/dispatch/update-lr', [
            'log_id' => $log->id,
            'lr_image' => $fakeBase64,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $log->refresh();
        $this->assertNotNull($log->lr_image_path);
        $this->assertFileExists(public_path($log->lr_image_path));

        // Clean up created file
        if (file_exists(public_path($log->lr_image_path))) {
            @unlink(public_path($log->lr_image_path));
        }
    }
}
