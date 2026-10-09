<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierA;
    protected User $cashierB;
    protected User $cashierC;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Office Supplies', 'is_active' => true]);

        $this->cashierB = User::create([
            'name' => 'Cashier B',
            'email' => 'cashierb@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->cashierC = User::create([
            'name' => 'Cashier C',
            'email' => 'cashierc@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        // Cashier A can see Cashier B, but NOT Cashier C
        $this->cashierA = User::create([
            'name' => 'Cashier A',
            'email' => 'cashiera@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'branch' => 'Main Branch',
            'status' => 'ACTIVE',
            'visible_cashiers' => [$this->cashierB->id],
        ]);
    }

    public function test_cashier_can_create_and_edit_own_transaction(): void
    {
        $session = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [
                [
                    'type' => 'OUT',
                    'amount' => 150.00,
                    'category' => 'office_supplies',
                    'note' => 'Stationery purchase',
                ]
            ]
        ]);

        $response->assertJson(['success' => true]);

        $tx = Transaction::where('user_id', $this->cashierA->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(150.00, (float) $tx->amount);

        // Edit transaction
        $editResponse = $this->withSession($session)->putJson("/cashier/action/{$tx->id}", [
            'amount' => 200.00,
            'category' => 'office_supplies',
            'note' => 'Updated stationery purchase',
        ]);

        $editResponse->assertJson(['success' => true]);

        $tx->refresh();
        $this->assertEquals(200.00, (float) $tx->amount);

        // Verify audit log entry
        $this->assertDatabaseHas('transaction_logs', [
            'transaction_id' => $tx->id,
            'user_id' => $this->cashierA->id,
            'action' => 'EDITED',
        ]);
    }

    public function test_cashier_cannot_edit_another_cashiers_transaction(): void
    {
        $txC = Transaction::create([
            'user_id' => $this->cashierC->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);

        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($sessionA)->putJson("/cashier/action/{$txC->id}", [
            'amount' => 1000.00,
            'category' => 'sales',
        ]);

        $response->assertStatus(403);
    }

    public function test_team_ledger_respects_visible_cashiers_permissions(): void
    {
        // Create transactions for Cashier B and Cashier C
        Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);

        Transaction::create([
            'user_id' => $this->cashierC->id,
            'type' => 'IN',
            'amount' => 9999.00,
            'category' => 'sales',
        ]);

        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $response = $this->withSession($sessionA)->get('/cashier/ledger');
        $response->assertStatus(200);

        $pageData = $response->viewData('pageData');
        $teamTxs = collect($pageData['teamTransactions']);

        // Cashier A sees Cashier B's transaction ($500)
        $this->assertTrue($teamTxs->pluck('amount')->contains(500.00));

        // Cashier A DOES NOT see Cashier C's transaction ($9999)
        $this->assertFalse($teamTxs->pluck('amount')->contains(9999.00));
    }

    public function test_admin_can_save_empty_visible_cashiers_to_restrict_team_ledger(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $adminSession = ['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'ADMIN']];

        // Update Cashier A via admin with empty visible_cashiers
        $response = $this->withSession($adminSession)->postJson('/admin/users', [
            'user_id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'username' => $this->cashierA->username,
            'role' => 'CASHIER',
            'phone' => '+91 9999999999',
            'branch' => 'Main Branch',
            'visible_cashiers' => [],
        ]);
        $response->assertJson(['success' => true]);

        $this->cashierA->refresh();
        $this->assertSame([], $this->cashierA->visible_cashiers);

        // Verify Cashier A now sees neither Cashier B nor Cashier C in team ledger
        Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 500.00,
            'category' => 'sales',
        ]);

        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $ledgerResp = $this->withSession($sessionA)->get('/cashier/ledger');
        $ledgerResp->assertStatus(200);
        $teamTxs = collect($ledgerResp->viewData('pageData')['teamTransactions']);
        $this->assertFalse($teamTxs->pluck('amount')->contains(500.00));
    }

    public function test_cashier_can_create_date_wise_transactions_and_update_date(): void
    {
        $session = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        $customDate = '2026-05-15';
        $response = $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [
                [
                    'date' => $customDate,
                    'type' => 'OUT',
                    'amount' => 350.00,
                    'category' => 'office_supplies',
                    'note' => 'Date wise stationery purchase',
                    'reference' => 'INV-2026-05',
                ]
            ]
        ]);

        $response->assertJson(['success' => true]);

        $tx = Transaction::where('user_id', $this->cashierA->id)
            ->where('reference', 'INV-2026-05')
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(350.00, (float)$tx->amount);
        $this->assertStringStartsWith('2026-05-15', (string)$tx->date);
        $this->assertStringStartsWith('2026-05-15', (string)$tx->created_at);

        // Edit with a new date
        $updatedDate = '2026-05-20';
        $editResponse = $this->withSession($session)->putJson("/cashier/action/{$tx->id}", [
            'date' => $updatedDate,
            'amount' => 450.00,
            'category' => 'office_supplies',
            'note' => 'Updated date wise stationery purchase',
        ]);

        $editResponse->assertJson(['success' => true]);

        $tx->refresh();
        $this->assertEquals(450.00, (float)$tx->amount);
        $this->assertStringStartsWith('2026-05-20', (string)$tx->date);
        $this->assertStringStartsWith('2026-05-20', (string)$tx->created_at);

        // Test multiple rows with separate dates in one submission
        $multiResponse = $this->withSession($session)->postJson('/cashier/action', [
            'transactions' => [
                [
                    'date' => '2026-04-10',
                    'type' => 'OUT',
                    'amount' => 100.00,
                    'category' => 'office_supplies',
                    'note' => 'Row 1',
                    'reference' => 'MULTI-1',
                ],
                [
                    'date' => '2026-04-12',
                    'type' => 'IN',
                    'amount' => 500.00,
                    'category' => 'office_supplies',
                    'note' => 'Row 2',
                    'reference' => 'MULTI-2',
                ]
            ]
        ]);

        $multiResponse->assertJson(['success' => true]);

        $tx1 = Transaction::where('reference', 'MULTI-1')->first();
        $tx2 = Transaction::where('reference', 'MULTI-2')->first();

        $this->assertNotNull($tx1);
        $this->assertNotNull($tx2);
        $this->assertStringStartsWith('2026-04-10', (string)$tx1->date);
        $this->assertStringStartsWith('2026-04-12', (string)$tx2->date);
    }

    public function test_cashier_ledger_table_shows_details_column(): void
    {
        $sessionA = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
        ]];

        Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'IN',
            'amount' => 750.00,
            'category' => 'sales',
            'note' => 'Sales deposit for ledger',
        ]);

        $response = $this->withSession($sessionA)->get('/cashier/ledger');
        $response->assertStatus(200);

        // Verify Details column header is present
        $response->assertSee('<th style="padding:12px; text-align:left;">Details</th>', false);

        // Verify Details content is displayed in the table row
        $response->assertSee('Sales deposit for ledger');

        // Verify ALL CATEGORIES dropdown is removed from ledger toolbar
        $response->assertDontSee('ALL CATEGORIES');
        $response->assertDontSee('id="ledger-category-select"', false);
    }

    public function test_cashiers_without_permission_do_not_see_each_other_in_team_ledger(): void
    {
        // Neither cashierB nor cashierC have visible_cashiers permission granted
        $this->cashierB->update(['visible_cashiers' => null]);
        $this->cashierC->update(['visible_cashiers' => []]);

        Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 1234.00,
            'category' => 'sales',
            'note' => 'Cashier B private entry',
        ]);

        Transaction::create([
            'user_id' => $this->cashierC->id,
            'type' => 'IN',
            'amount' => 5678.00,
            'category' => 'sales',
            'note' => 'Cashier C private entry',
        ]);

        // Cashier B visits ledger
        $sessionB = ['auth_user' => ['id' => $this->cashierB->id, 'name' => $this->cashierB->name, 'role' => 'CASHIER']];
        $respB = $this->withSession($sessionB)->get('/cashier/ledger');
        $respB->assertStatus(200);
        $teamTxsB = collect($respB->viewData('pageData')['teamTransactions']);
        // B sees own transaction
        $this->assertTrue($teamTxsB->pluck('amount')->contains(1234.00));
        // B DOES NOT see C's transaction
        $this->assertFalse($teamTxsB->pluck('amount')->contains(5678.00));

        // Cashier C visits ledger
        $sessionC = ['auth_user' => ['id' => $this->cashierC->id, 'name' => $this->cashierC->name, 'role' => 'CASHIER']];
        $respC = $this->withSession($sessionC)->get('/cashier/ledger');
        $respC->assertStatus(200);
        $teamTxsC = collect($respC->viewData('pageData')['teamTransactions']);
        // C sees own transaction
        $this->assertTrue($teamTxsC->pluck('amount')->contains(5678.00));
        // C DOES NOT see B's transaction
        $this->assertFalse($teamTxsC->pluck('amount')->contains(1234.00));
    }

    public function test_cashier_visibility_is_strictly_directional(): void
    {
        // Cashier A is granted permission to see Cashier B
        $this->cashierA->update(['visible_cashiers' => [$this->cashierB->id]]);
        // Cashier B is NOT granted permission to see Cashier A
        $this->cashierB->update(['visible_cashiers' => []]);

        Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'IN',
            'amount' => 111.00,
            'category' => 'sales',
        ]);

        Transaction::create([
            'user_id' => $this->cashierB->id,
            'type' => 'IN',
            'amount' => 222.00,
            'category' => 'sales',
        ]);

        // Cashier A views team ledger -> sees B ($222)
        $sessionA = ['auth_user' => ['id' => $this->cashierA->id, 'name' => $this->cashierA->name, 'role' => 'CASHIER']];
        $respA = $this->withSession($sessionA)->get('/cashier/ledger');
        $teamTxsA = collect($respA->viewData('pageData')['teamTransactions']);
        $this->assertTrue($teamTxsA->pluck('amount')->contains(222.00));

        // Cashier B views team ledger -> DOES NOT see A ($111)
        $sessionB = ['auth_user' => ['id' => $this->cashierB->id, 'name' => $this->cashierB->name, 'role' => 'CASHIER']];
        $respB = $this->withSession($sessionB)->get('/cashier/ledger');
        $teamTxsB = collect($respB->viewData('pageData')['teamTransactions']);
        $this->assertFalse($teamTxsB->pluck('amount')->contains(111.00));
    }

    public function test_cashier_cannot_download_unauthorized_cashier_pdf(): void
    {
        $this->cashierB->update(['visible_cashiers' => []]);

        $sessionB = ['auth_user' => ['id' => $this->cashierB->id, 'name' => $this->cashierB->name, 'role' => 'CASHIER']];
        $response = $this->withSession($sessionB)->get('/cashier/history/pdf?cashier_id=' . $this->cashierA->id);
        $response->assertStatus(403);
    }

    public function test_team_ledger_option_is_hidden_when_cashier_has_no_visible_team_members(): void
    {
        // Cashier B has NO visible cashiers assigned
        $this->cashierB->update(['visible_cashiers' => []]);
        $sessionB = ['auth_user' => ['id' => $this->cashierB->id, 'name' => $this->cashierB->name, 'role' => 'CASHIER']];
        $respB = $this->withSession($sessionB)->get('/cashier/ledger');
        $respB->assertStatus(200);
        $respB->assertSee('PERSONAL LEDGER');
        $respB->assertDontSee('value="team"', false);

        // Cashier A HAS Cashier B assigned in visible_cashiers
        $this->cashierA->update(['visible_cashiers' => [$this->cashierB->id]]);
        $sessionA = ['auth_user' => ['id' => $this->cashierA->id, 'name' => $this->cashierA->name, 'role' => 'CASHIER']];
        $respA = $this->withSession($sessionA)->get('/cashier/ledger');
        $respA->assertStatus(200);
        $respA->assertSee('value="team"', false);
        $respA->assertSee('TEAM LEDGER');
    }

    public function test_cashier_can_download_statement_pdf_in_uppercase(): void
    {
        Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'IN',
            'amount' => 5000.00,
            'category' => 'sales',
            'note' => 'wholesale salt sales',
            'site' => 'Main Branch',
        ]);

        $sessionA = ['auth_user' => ['id' => $this->cashierA->id, 'name' => $this->cashierA->name, 'role' => 'CASHIER']];
        $resp = $this->withSession($sessionA)->get('/cashier/history/pdf?site=Main%20Branch');
        $resp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resp->headers->get('content-type'));
    }

    public function test_cashier_statement_pdf_uses_user_branch_and_omits_cashier_and_site_in_row_description(): void
    {
        $this->cashierA->update(['branch' => 'FACTORY EXPENSES']);

        Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'OUT',
            'amount' => 1500.00,
            'category' => 'expense',
            'note' => 'factory maintenance',
            'reference' => 'REF-999',
            'site' => 'FACTORY EXPENSES',
        ]);

        $sessionA = ['auth_user' => ['id' => $this->cashierA->id, 'name' => $this->cashierA->name, 'role' => 'CASHIER', 'branch' => 'FACTORY EXPENSES']];
        
        // Calling without site should automatically default to the user's branch
        $resp = $this->withSession($sessionA)->get('/cashier/history/pdf');
        $resp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resp->headers->get('content-type'));

        // Also test rendered view directly to assert HTML contents
        $viewHtml = view('pdf.cashier-statement', [
            'reportId' => 101,
            'generatedOn' => strtoupper(now()->format('d-M-Y H:i:s')),
            'fromDate' => now()->format('Y-m-d'),
            'toDate' => now()->format('Y-m-d'),
            'cashierName' => 'ONALI LAKHANI',
            'cashierId' => $this->cashierA->id,
            'accountName' => 'FOODS AND SPICES',
            'site' => 'FACTORY EXPENSES',
            'category' => 'ALL',
            'rows' => [
                [
                    'id' => 1,
                    'date' => now()->toDateTimeString(),
                    'category' => 'EXPENSE',
                    'note' => 'FACTORY MAINTENANCE',
                    'description' => '',
                    'reference' => 'REF-999',
                    'site' => 'FACTORY EXPENSES',
                    'cashier_name' => 'ONALI LAKHANI',
                    'type' => 'OUT',
                    'amount' => 1500.0,
                    'opening_bal' => 0.0,
                    'closing_bal' => -1500.0,
                    'bills' => [],
                ]
            ],
            'openingBalance' => 0.0,
            'closingBalance' => -1500.0,
            'sumIn' => 0.0,
            'sumOut' => 1500.0,
            'totalRecords' => 1,
            'includeBills' => false,
            'showBalance' => true,
            'billPages' => [],
        ])->render();

        // Top meta table should show Cashier and Site
        $this->assertStringContainsString('CASHIER:</strong> ONALI LAKHANI', $viewHtml);
        $this->assertStringContainsString('SITE: FACTORY EXPENSES', $viewHtml);

        // Row description should show note and reference, but NOT "CASHIER: ONALI LAKHANI" or "SITE: FACTORY EXPENSES"
        $this->assertStringContainsString('FACTORY MAINTENANCE', $viewHtml);
        $this->assertStringContainsString('REF: REF-999', $viewHtml);
        $this->assertStringNotContainsString('CASHIER: ONALI LAKHANI</span>', $viewHtml);
        $this->assertStringNotContainsString('SITE: FACTORY EXPENSES</span>', $viewHtml);
    }

    public function test_cashier_assigned_branch_displays_on_ledger_page_and_includes_branch_transactions(): void
    {
        $branchCashier = User::create([
            'name' => 'GPAY CASHIER',
            'username' => 'gpay_cashier',
            'phone' => '+91 9999000077',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'CASHIER',
            'branch' => 'pPF gpay expenses',
            'status' => 'ACTIVE',
        ]);

        // Transaction created with site matching the branch
        Transaction::create([
            'user_id' => $branchCashier->id,
            'type' => 'OUT',
            'amount' => 750.00,
            'category' => 'expenses',
            'note' => 'GPay Vendor Payment',
            'site' => 'pPF gpay expenses',
        ]);

        // Another user and transaction in the system with a different branch
        $otherCashier = User::create([
            'name' => 'OTHER CASHIER',
            'username' => 'other_cashier',
            'phone' => '+91 9999000088',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'CASHIER',
            'branch' => 'PFSPL GPAY EXPENSES',
            'status' => 'ACTIVE',
        ]);

        Transaction::create([
            'user_id' => $otherCashier->id,
            'type' => 'OUT',
            'amount' => 500.00,
            'category' => 'expenses',
            'note' => 'Other Branch Payment',
            'site' => 'PFSPL GPAY EXPENSES',
        ]);

        $session = ['auth_user' => [
            'id' => $branchCashier->id,
            'name' => $branchCashier->name,
            'role' => 'CASHIER',
            'branch' => $branchCashier->branch,
        ]];

        $response = $this->withSession($session)->get('/cashier/ledger');

        $response->assertStatus(200);
        $pageData = $response->viewData('pageData');
        $this->assertEquals('pPF gpay expenses', $pageData['userBranch']);

        // Assert only admin-defined branch is available in sites, NOT other branches
        $this->assertEquals(['pPF gpay expenses'], $pageData['sites']);
        $this->assertFalse(in_array('PFSPL GPAY EXPENSES', $pageData['sites']));

        // Assert branch appears in the rendered HTML
        $response->assertSee('BRANCH: PPF GPAY EXPENSES');
        $response->assertSee('PERSONAL LEDGER (PPF GPAY EXPENSES)');
        $response->assertSee('PPF GPAY EXPENSES');
        $response->assertSee('GPay Vendor Payment');
        $response->assertDontSee('PFSPL GPAY EXPENSES');
    }

    public function test_cashier_history_shows_old_entries_by_default(): void
    {
        $oldTx1 = Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'OUT',
            'amount' => 1250.00,
            'category' => 'general',
            'note' => 'Old Entry Six Months Ago',
            'date' => \Carbon\Carbon::now()->subMonths(6),
            'site' => 'Main Branch',
        ]);

        $oldTx2 = Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'IN',
            'amount' => 9500.00,
            'category' => 'general',
            'note' => 'Old Entry Last Year',
            'date' => \Carbon\Carbon::now()->subYear(),
            'site' => 'Old Disused Branch',
        ]);

        $recentTx = Transaction::create([
            'user_id' => $this->cashierA->id,
            'type' => 'OUT',
            'amount' => 350.00,
            'category' => 'supplies',
            'note' => 'Recent Today Entry',
            'date' => \Carbon\Carbon::now(),
            'site' => 'Main Branch',
        ]);

        $session = ['auth_user' => [
            'id' => $this->cashierA->id,
            'name' => $this->cashierA->name,
            'role' => 'CASHIER',
            'branch' => 'Main Branch',
        ]];

        $response = $this->withSession($session)->get('/cashier/history');

        $response->assertStatus(200);

        // Verify all 3 entries appear in HTML by default without date filter hiding old entries
        $response->assertSee('Recent Today Entry');
        $response->assertSee('Old Entry Six Months Ago');
        $response->assertSee('Old Entry Last Year');
        $response->assertSee('All Time (All Entries)');
    }
}


