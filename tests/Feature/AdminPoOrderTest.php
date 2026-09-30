<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPoOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_po_orders_by_pending_first_then_read_then_complete(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_po_test@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $product = Product::create([
            'name' => 'Raw Corn',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        // Create received PO first (earliest created_at)
        $received = PurchaseOrder::create([
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'status' => 'RECEIVED',
            'note' => 'NOTE_RECEIVED',
            'created_at' => now()->subDays(1),
        ]);

        // Create read PO
        $read = PurchaseOrder::create([
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'quantity' => 200,
            'status' => 'READ',
            'note' => 'NOTE_READ',
            'created_at' => now()->subDays(2),
        ]);

        // Create pending PO (even though created longest ago, it must show FIRST)
        $pending = PurchaseOrder::create([
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'quantity' => 300,
            'status' => 'PENDING',
            'note' => 'NOTE_PENDING',
            'created_at' => now()->subDays(3),
        ]);

        $session = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];
        $response = $this->withSession($session)->get('/admin/po');
        $response->assertStatus(200);

        $content = $response->getContent();

        $posPending = strpos($content, 'NOTE_PENDING');
        $posRead = strpos($content, 'NOTE_READ');
        $posReceived = strpos($content, 'NOTE_RECEIVED');

        $this->assertNotFalse($posPending, 'NOTE_PENDING must be in page content');
        $this->assertNotFalse($posRead, 'NOTE_READ must be in page content');
        $this->assertNotFalse($posReceived, 'NOTE_RECEIVED must be in page content');

        // Verify order: PENDING comes before READ, and READ comes before RECEIVED
        $this->assertTrue($posPending < $posRead, 'PENDING PO must appear before READ PO');
        $this->assertTrue($posRead < $posReceived, 'READ PO must appear before RECEIVED PO');
    }
}
