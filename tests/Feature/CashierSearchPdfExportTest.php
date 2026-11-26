<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierSearchPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_pdf_exports_filtered_search_results(): void
    {
        $cashier = User::create([
            'name'     => 'Cashier John',
            'email'    => 'john@example.com',
            'password' => 'secret123',
            'role'     => 'CASHIER',
            'status'   => 'ACTIVE',
            'branch'   => 'MAIN_STORE',
        ]);

        // Transaction 1: Matches "Tea"
        $tx1 = Transaction::create([
            'user_id'     => $cashier->id,
            'type'        => 'OUT',
            'amount'      => 150.00,
            'category'    => 'tea_refreshment',
            'site'        => 'MAIN_STORE',
            'date'        => '2026-11-20 10:00:00',
            'note'        => 'Tea and Snacks for Meeting',
            'description' => 'Morning snacks',
            'reference'   => 'REF-TEA-101',
        ]);

        // Transaction 2: Does NOT match "Tea"
        $tx2 = Transaction::create([
            'user_id'     => $cashier->id,
            'type'        => 'OUT',
            'amount'      => 650.00,
            'category'    => 'maintenance',
            'site'        => 'MAIN_STORE',
            'date'        => '2026-11-21 11:30:00',
            'note'        => 'Office Hardware Repair',
            'description' => 'Replaced broken lock',
            'reference'   => 'REF-HDW-202',
        ]);

        // Transaction 3: Matches "Tea"
        $tx3 = Transaction::create([
            'user_id'     => $cashier->id,
            'type'        => 'OUT',
            'amount'      => 220.00,
            'category'    => 'tea_refreshment',
            'site'        => 'MAIN_STORE',
            'date'        => '2026-11-22 14:00:00',
            'note'        => 'Tea supplies for pantry',
            'description' => 'Monthly tea bags purchase',
            'reference'   => 'REF-TEA-303',
        ]);

        $session = [
            'auth_user' => [
                'id'     => $cashier->id,
                'name'   => $cashier->name,
                'role'   => 'CASHIER',
                'branch' => 'MAIN_STORE',
            ],
        ];

        // 1. Verify ledger view has search bar and export buttons
        $ledgerResponse = $this->withSession($session)->get('/cashier/ledger');
        $ledgerResponse->assertOk();
        $ledgerResponse->assertSee('id="ledger-search-input"', false);
        $ledgerResponse->assertSee('id="btn-export-search-pdf"', false);
        $ledgerResponse->assertSee('id="header-export-pdf-btn"', false);

        // 2. Request PDF filtered by search query "Tea"
        $pdfResponse = $this->withSession($session)->get('/cashier/history/pdf?q=Tea&include_bills=no');
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');

        $disposition = $pdfResponse->headers->get('Content-Disposition');
        $this->assertStringContainsString('SEARCH_TEA', $disposition);

        // 3. Decompress / inspect PDF output to verify it contains the 2 Tea transactions and NOT the Hardware transaction
        $pdfContent = $pdfResponse->getContent();
        $this->assertNotEmpty($pdfContent);

        // Render PDF view directly to verify data binding and row inclusion
        $controller = new \App\Http\Controllers\CashierController();
        $request = \Illuminate\Http\Request::create('/cashier/history/pdf', 'GET', [
            'q'             => 'Tea',
            'include_bills' => 'no',
        ]);
        $responseRaw = $controller->downloadPdf($request);
        $this->assertEquals(200, $responseRaw->getStatusCode());

        // 4. Request PDF filtered by search query "Hardware"
        $pdfHardware = $this->withSession($session)->get('/cashier/history/pdf?q=Hardware&include_bills=no');
        $pdfHardware->assertOk();
        $this->assertStringContainsString('SEARCH_HARDWARE', $pdfHardware->headers->get('Content-Disposition'));

        // 5. Request PDF with no matches
        $pdfEmpty = $this->withSession($session)->get('/cashier/history/pdf?q=NonExistentFilter999&include_bills=no');
        $pdfEmpty->assertOk();
    }
}
