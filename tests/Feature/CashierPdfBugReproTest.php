<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashierPdfBugReproTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_pdf_with_bills_and_custom_site(): void
    {
        Storage::fake('public');

        $cashier = User::create([
            'name' => 'Cashier Three',
            'email' => 'cashier3@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'branch' => 'pPF OTHER expenses',
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg', 600, 400);
        $path = $file->storeAs('bills/1', 'receipt.jpg', 'public');

        // Create transaction with string date and bill
        $tx = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 1500.00,
            'category' => 'other_expenses',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-21 12:00:00',
            'note' => 'Office Supplies',
        ]);

        TransactionBill::create([
            'transaction_id' => $tx->id,
            'file_path' => $path,
            'file_type' => 'image',
            'original_name' => 'receipt.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 1,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'role' => 'CASHIER',
            ]
        ])->get('/cashier/history/pdf?include_bills=yes&site=' . urlencode('pPF OTHER expenses') . '&tab=personal');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_cashier_pdf_without_bills(): void
    {
        $cashier = User::create([
            'name' => 'Cashier Three',
            'email' => 'cashier3_no_bills@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'branch' => 'pPF OTHER expenses',
        ]);

        Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'IN',
            'amount' => 5000.00,
            'category' => 'capital',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-20',
            'note' => 'Opening Cash',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'role' => 'CASHIER',
            ]
        ])->get('/cashier/history/pdf?include_bills=no&site=' . urlencode('pPF OTHER expenses') . '&tab=personal');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_cashier_pdf_handles_missing_bill_file_gracefully(): void
    {
        $cashier = User::create([
            'name' => 'Cashier Three',
            'email' => 'cashier3_missing@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
            'branch' => 'pPF OTHER expenses',
        ]);

        $tx = Transaction::create([
            'user_id' => $cashier->id,
            'type' => 'OUT',
            'amount' => 250.00,
            'category' => 'tea_snacks',
            'site' => 'pPF OTHER expenses',
            'date' => '2026-10-21',
            'note' => 'Snacks',
        ]);

        TransactionBill::create([
            'transaction_id' => $tx->id,
            'file_path' => 'bills/999/non_existent.jpg',
            'file_type' => 'image',
            'original_name' => 'non_existent.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 1,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'role' => 'CASHIER',
            ]
        ])->get('/cashier/history/pdf?include_bills=yes&site=' . urlencode('pPF OTHER expenses') . '&tab=personal');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }
}
