<?php

namespace Tests\Feature;

use App\Http\Controllers\DispatchController;
use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\DispatchLogItem;
use App\Models\DispatchLrBackup;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'name' => 'KWALITY THAI FOODS PRIVATE LIMITED',
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
            DispatchController::deletePhysicalLrCopies($rp, true);
        }
        parent::tearDown();
    }

    protected function samplePngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }

    protected function samplePngDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    protected function subAdminSession(): array
    {
        return [
            'auth_user' => $this->subAdmin->toArray(),
        ];
    }

    public function test_missing_legacy_lr_shows_clean_ui_recovers_on_upload_and_self_heals_from_mysql_backup(): void
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

        // 1. History page renders clean upload prompt + Restore Old LRs button without broken <img>
        $historyResp = $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/history');

        $historyResp->assertStatus(200);
        $historyResp->assertSee('LR FILE MISSING');
        $historyResp->assertSee('Restore Old LRs (1)');
        $historyResp->assertDontSee('/dispatch/view-lr/' . $log->id);

        // 2. Upload LR via Sub Admin saves to disk AND dispatch_lr_backups in MySQL
        $uploadResp = $this->withSession($this->subAdminSession())
            ->postJson('/sub_admin/dispatch/update-lr', [
                'log_id' => $log->id,
                'lr_image' => $this->samplePngDataUrl(),
            ]);

        $uploadResp->assertStatus(200)->assertJson(['success' => true]);

        $log->refresh();
        $this->createdLrPaths[] = $log->lr_image_path;
        $basename = basename($log->lr_image_path);

        $this->assertDatabaseHas('dispatch_lr_backups', [
            'file_name' => $basename,
            'dispatch_log_id' => $log->id,
        ]);

        // 3. Simulate a complete server disk wipe (delete all physical copies, keep MySQL backup)
        DispatchController::deletePhysicalLrCopies($log->lr_image_path, false);
        $this->assertNull(DispatchController::findExistingDiskFileOnly($log->lr_image_path));

        // 4. Visiting Dispatch History automatically self-heals the physical .jpg file from MySQL!
        $healedHistory = $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/history');

        $healedHistory->assertStatus(200);
        $healedHistory->assertSee('LR UPLOADED');
        $healedHistory->assertDontSee('LR FILE MISSING');
        $this->assertNotNull(DispatchController::findExistingDiskFileOnly($log->lr_image_path));

        // 5. Streaming and downloading both return 200 OK
        $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/view-lr/' . $log->id)
            ->assertStatus(200);

        $this->withSession($this->subAdminSession())
            ->get('/sub_admin/dispatch/download-lr/' . $log->id)
            ->assertStatus(200);
    }

    public function test_bulk_zip_restore_and_clear_missing_lr_work_properly(): void
    {
        $targetFilename = 'LR_test_zip_restore_order3.jpg';
        $targetRelPath = 'lr_images/' . $targetFilename;
        $this->createdLrPaths[] = $targetRelPath;
        DispatchController::deletePhysicalLrCopies($targetRelPath, true);

        $log = DispatchLog::create([
            'order_id' => $this->order->id,
            'user_id' => $this->subAdmin->id,
            'lr_image_path' => $targetRelPath,
        ]);

        DispatchLogItem::create([
            'dispatch_log_id' => $log->id,
            'order_item_id' => $this->orderItem->id,
            'quantity' => 800,
        ]);

        // Build an in-memory ZIP mimicking hPanel backup structure (public_html/lr_images/LR_...)
        $tmpZipPath = storage_path('app/test_lr_backup_' . uniqid() . '.zip');
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmpZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $zip->addFromString('public_html/lr_images/' . $targetFilename, $this->samplePngBytes());
        $zip->close();

        $uploadedZip = new UploadedFile($tmpZipPath, 'lr_images_backup.zip', 'application/zip', null, true);

        $restoreResp = $this->withSession($this->subAdminSession())
            ->post('/sub_admin/dispatch/restore-lr-archive', [
                'files' => [$uploadedZip],
            ], ['Accept' => 'application/json']);

        @unlink($tmpZipPath);

        $restoreResp->assertStatus(200)->assertJson([
            'success' => true,
            'restored_count' => 1,
        ]);

        $this->assertNotNull(DispatchController::findExistingDiskFileOnly($targetRelPath));
        $this->assertTrue(DispatchLrBackup::where('file_name', $targetFilename)->exists());

        // Also test clearMissingLR on a genuinely unrecoverable record
        $log2 = DispatchLog::create([
            'order_id' => $this->order->id,
            'user_id' => $this->subAdmin->id,
            'lr_image_path' => 'lr_images/LR_unrecoverable_old.jpg',
        ]);

        $clearResp = $this->withSession($this->subAdminSession())
            ->postJson('/sub_admin/dispatch/clear-missing-lr/' . $log2->id);

        $clearResp->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNull($log2->fresh()->lr_image_path);
    }
}
