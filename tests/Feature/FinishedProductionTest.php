<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinishedProductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $finishedUser;
    protected Product $semiInputProduct;
    protected Product $finishedProduct;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finishedUser = User::create([
            'name' => 'Finished Manager',
            'email' => 'finished@example.com',
            'password' => 'password123',
            'role' => 'FINISHED',
            'status' => 'ACTIVE',
        ]);

        $this->semiInputProduct = Product::create([
            'name' => 'Semi Base Material',
            'type' => 'SEMI',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->finishedProduct = Product::create([
            'name' => 'Finished Pipe 110mm',
            'type' => 'FINISHED',
            'unit' => 'm',
            'is_active' => true,
        ]);

        $this->location = Location::create(['name' => 'Main Warehouse']);
    }

    public function test_finished_production_fails_when_input_stock_insufficient(): void
    {
        $response = $this->withSession(['auth_user' => [
            'id' => $this->finishedUser->id,
            'name' => $this->finishedUser->name,
            'role' => 'FINISHED',
        ]])->postJson('/finished/action', [
            'output_product_id' => $this->finishedProduct->id,
            'output_grade' => 'HDPE',
            'output_qty' => 50,
            'inputs' => [
                [
                    'product_id' => $this->semiInputProduct->id,
                    'grade' => 'Standard',
                    'stage' => 'SEMI',
                    'quantity' => 100,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_finished_production_succeeds_and_creates_stock_entries(): void
    {
        // Add 200kg SEMI stock
        Stock::create([
            'product_id' => $this->semiInputProduct->id,
            'user_id' => $this->finishedUser->id,
            'stage' => 'SEMI',
            'grade' => 'Standard',
            'location_id' => $this->location->id,
            'quantity' => 200,
            'transaction_type' => 'IN',
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $this->finishedUser->id,
            'name' => $this->finishedUser->name,
            'role' => 'FINISHED',
        ]])->postJson('/finished/action', [
            'output_product_id' => $this->finishedProduct->id,
            'output_grade' => 'HDPE',
            'output_qty' => 50,
            'inputs' => [
                [
                    'product_id' => $this->semiInputProduct->id,
                    'grade' => 'Standard',
                    'stage' => 'SEMI',
                    'quantity' => 100,
                ],
            ],
        ]);

        $response->assertJson(['success' => true]);

        // Verify SEMI stock OUT deduction
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->semiInputProduct->id,
            'stage' => 'SEMI',
            'grade' => 'Standard',
            'quantity' => 100,
            'transaction_type' => 'OUT',
        ]);

        // Verify FINISHED stock IN creation
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->finishedProduct->id,
            'stage' => 'FINISHED',
            'grade' => 'HDPE',
            'quantity' => 50,
            'transaction_type' => 'IN',
        ]);
    }
}
