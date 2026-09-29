<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionUsageTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): array
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'ADMIN',
            'status' => 'ACTIVE'
        ]);
        return [
            'user' => $admin,
            'session' => ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']]
        ];
    }

    public function test_can_delete_user_with_no_data(): void
    {
        $auth = $this->createAdmin();
        $targetUser = User::factory()->create([
            'name' => 'Empty User',
            'username' => 'emptyuser',
            'role' => 'CASHIER',
            'status' => 'ACTIVE'
        ]);

        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/users/{$targetUser->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_cannot_delete_user_with_transactions(): void
    {
        $auth = $this->createAdmin();
        $cashier = User::factory()->create([
            'name' => 'Sneha Cashier',
            'username' => 'snehacashier',
            'role' => 'CASHIER',
            'status' => 'ACTIVE'
        ]);

        Transaction::create([
            'user_id' => $cashier->id,
            'amount' => 500,
            'type' => 'IN',
            'mode' => 'CASH',
            'description' => 'Test Transaction',
            'date' => now()->toDateString(),
            'branch' => 'Main',
        ]);

        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/users/{$cashier->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('associated data', $response->json('message'));
        $this->assertStringContainsString('Cashier Transaction', $response->json('message'));
        $this->assertDatabaseHas('users', ['id' => $cashier->id]);

        // Verify that the view renders the disabled button with tooltip
        $viewResponse = $this->withSession($auth['session'])->get('/admin/users');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Cannot delete: User has 1 associated record(s) in system.');
    }

    public function test_cannot_delete_user_with_purchase_orders(): void
    {
        $auth = $this->createAdmin();
        $rawUser = User::factory()->create([
            'name' => 'Amit Raw',
            'username' => 'amitraw',
            'role' => 'RAW',
            'status' => 'ACTIVE'
        ]);

        $product = Product::create([
            'name' => 'Raw Spice Item',
            'type' => 'RAW',
            'unit' => 'KG',
        ]);

        PurchaseOrder::create([
            'user_id' => $rawUser->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'status' => 'PENDING',
            'reason' => 'Stock low',
        ]);

        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/users/{$rawUser->id}");

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Purchase Request', $response->json('message'));
        $this->assertDatabaseHas('users', ['id' => $rawUser->id]);
    }

    public function test_cannot_delete_self_or_super_admin(): void
    {
        $auth = $this->createAdmin();

        // Cannot delete self
        $response = $this->withSession($auth['session'])
            ->deleteJson("/admin/users/{$auth['user']->id}");
        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Cannot delete yourself!']);

        // Cannot delete super admin
        $anotherAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'role' => 'ADMIN',
            'status' => 'ACTIVE'
        ]);
        $response2 = $this->withSession($auth['session'])
            ->deleteJson("/admin/users/{$anotherAdmin->id}");
        $response2->assertStatus(422);
        $response2->assertJson(['success' => false]);
        $this->assertStringContainsString('Super Admin cannot be deleted', $response2->json('message'));
    }
}
