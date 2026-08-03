<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawSemiTest extends TestCase
{
    use RefreshDatabase;

    protected User $rawUser;
    protected User $semiUser;
    protected Product $rawProduct;
    protected Product $semiProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rawUser = User::create([
            'name' => 'Raw Manager',
            'email' => 'raw@example.com',
            'password' => 'password123',
            'role' => 'RAW',
            'status' => 'ACTIVE',
        ]);

        $this->semiUser = User::create([
            'name' => 'Semi Manager',
            'email' => 'semi@example.com',
            'password' => 'password123',
            'role' => 'SEMI',
            'status' => 'ACTIVE',
        ]);

        $this->rawProduct = Product::create([
            'name' => 'Raw Polymer',
            'type' => 'RAW',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->semiProduct = Product::create([
            'name' => 'Semi Resin Granules',
            'type' => 'SEMI',
            'unit' => 'kg',
            'is_active' => true,
        ]);
    }

    public function test_raw_user_can_add_inward_stock(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->rawUser->id,
            'name' => $this->rawUser->name,
            'role' => 'RAW',
        ]])->postJson('/raw/action', [
            'product_id' => $this->rawProduct->id,
            'quantity' => 500,
            'grade' => 'A',
            'location' => 'Raw Warehouse',
        ]);

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->rawProduct->id,
            'stage' => 'RAW',
            'grade' => 'A',
            'quantity' => 500,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_transfer_to_semi_fails_if_insufficient_raw_stock(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->rawUser->id,
            'name' => $this->rawUser->name,
            'role' => 'RAW',
        ]])->postJson('/raw/transfer-to-semi', [
            'product_id' => $this->rawProduct->id,
            'quantity' => 1000,
            'grade' => 'A',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    public function test_transfer_to_semi_succeeds_and_creates_atomic_stock_movements(): void
    {
        // First add 500kg RAW
        $location = Location::create(['name' => 'Main Warehouse']);

        Stock::create([
            'product_id' => $this->rawProduct->id,
            'user_id' => $this->rawUser->id,
            'stage' => 'RAW',
            'grade' => 'A',
            'location_id' => $location->id,
            'quantity' => 500,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $this->rawUser->id,
            'name' => $this->rawUser->name,
            'role' => 'RAW',
        ]])->postJson('/raw/transfer-to-semi', [
            'product_id' => $this->rawProduct->id,
            'quantity' => 200,
            'grade' => 'A',
        ]);

        $response->assertJson(['success' => true]);

        // RAW should have an OUT of 200
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->rawProduct->id,
            'stage' => 'RAW',
            'grade' => 'A',
            'quantity' => 200,
            'transaction_type' => 'OUT',
        ]);

        // SEMI should have an IN of 200
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->rawProduct->id,
            'stage' => 'SEMI',
            'grade' => 'A',
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);
    }

    public function test_semi_production_fails_when_input_stock_insufficient(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->semiUser->id,
            'name' => $this->semiUser->name,
            'role' => 'SEMI',
        ]])->postJson('/semi/action', [
            'output_product_id' => $this->semiProduct->id,
            'output_grade' => 'Grade-1',
            'output_qty' => 100,
            'inputs' => [
                [
                    'product_id' => $this->rawProduct->id,
                    'grade' => 'A',
                    'quantity' => 150,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }
}
