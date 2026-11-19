<?php

namespace Tests\Feature;

use App\Http\Controllers\DispatchController;
use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\DispatchLogItem;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchLrImageHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $subAdmin;
    protected Order $order;
    protected OrderItem $orderItem;
    protected array $createdLrPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subAdmin = User::create([
            'name' => 'STOCK & LR 920',
            'email' => 'stocklr920@example.com',
            'password' => 'password123',
            'role' => 'SUB_ADMIN',
            'status' => 'ACTIVE',
            'permissions' => ['dispatch_history', 'dispatch_action', 'edit_dispatch_action', 'edit_dispatch_history'],
        ]);

        $company = Company::create([
            'name' => 'APRICOT FOODS PVT. LTD',
            'contact' => '+91 9988776655',
            'address' => 'Gujarat',
        ]);

        $transporter = Transporter::create([
            'name' => 'SHREE RAJESHWARI TRANSPORT',
            'contact' => '+91 8877665544',
        ]);

        $product = Product::create([
            'name' => 'DEHYDRATED PINK ONION POWDER',
            'type' => 'FINISHED',
            'unit' => 'KG',
            'is_active' => true,
        ]);

        Location::create(['name' => 'AC ROOM']);

        $this->order = Order::create([
            'created_by' => $this->subAdmin->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'total' => 80000,
            'status' => 'CLOSED',
            'dispatch_status' => 'DONE',
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'grade' => 'NONE',
            'quantity' => 800,
            'price' => 100,
            'dispatched_qty' => 800,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdLrPaths as $rp) {
            DispatchController::deletePhysicalLrCopies($rp);
        }
        parent::tearDown();
    }

    protected function samplePngDataUrl(): string
    {
        // Valid 1x1 PNG (>60 bytes)
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    protected function subAdminSession(): array
    {
        return [
            'auth_user' => $this->subAdmin->toArray(),
        ];
    }

    public function test_missing_legacy_lr_file_shows_honest_missing_ui_without_broken_img_and_recovers_on_reupload(): void
    {
        $log = DispatchLog::create([
            'order_id' => $this->order->id,
            'user_id' => $this->subAdmin->id,
            'lr_image_path' => 'lr_images/LR_nonexistent_legacy_test_file.jpg',
        ]);

        DispatchLogItem::create([
            'dispatch_log_id' => $log->id,
            'order_item_id' => $this->orderItem->id,
            'quantity' => 800,
        ]);

        // 1. History page must NOT render a broken <img> tag or misleading LR UPLOADED badge for missing disk file
        $historyResp = $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/history');

        $historyResp->assertStatus(200);
        $historyResp->assertSee('LR FILE MISSING');
        $historyResp->assertSee('Previous LR Image File Missing on Server');
        $historyResp->assertDontSee('/dispatch/view-lr/' . $log->id);

        // 2. Streaming missing LR returns 404 cleanly
        $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/view-lr/' . $log->id)
            ->assertStatus(404);

        // 3. Re-uploading via Sub Admin slug restores physical image & vault copies
        $uploadResp = $this->withSession($this->subAdminSession())
            ->postJson('/sub_admin/dispatch/update-lr', [
                'log_id' => $log->id,
                'lr_image' => $this->samplePngDataUrl(),
            ]);

        $uploadResp->assertStatus(200)->assertJson(['success' => true]);

        $log->refresh();
        $this->createdLrPaths[] = $log->lr_image_path;
        $this->assertNotNull(DispatchController::resolveLocalFilePath($log->lr_image_path));
        $this->assertFileExists(storage_path('app/lr_vault/' . basename($log->lr_image_path)));

        // 4. History page now shows LR UPLOADED and cache-busted stream URL
        $historyAfter = $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/history');

        $historyAfter->assertStatus(200);
        $historyAfter->assertSee('LR UPLOADED');
        $historyAfter->assertDontSee('LR FILE MISSING');
        $historyAfter->assertSee('/sub_admin/dispatch/view-lr/' . $log->id . '?idx=0&amp;v=', false);

        // 5. Both view-lr and download-lr with {user_slug} prefix return 200 OK
        $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/view-lr/' . $log->id)
            ->assertStatus(200);

        $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/download-lr/' . $log->id)
            ->assertStatus(200);
    }
}
