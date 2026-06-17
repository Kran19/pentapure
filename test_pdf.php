<?php

use Illuminate\Support\Facades\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Creating request...\n";
    $req = Request::create('/cashier/history/pdf', 'GET', [
        'from' => '2024-01-01',
        'to' => '2027-01-01',
        'include_bills' => 'yes'
    ]);
    $controller = new \App\Http\Controllers\CashierController();
    $user = \App\Models\User::where('role', 'CASHIER')->first();
    if (!$user) {
        echo "No cashier user found\n";
        exit;
    }
    echo "Found user: {$user->id}\n";
    
    // Create dummy transaction to ensure there is at least one
    if (\App\Models\Transaction::where('user_id', $user->id)->count() === 0) {
        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'type' => 'IN',
            'amount' => 100,
            'category' => 'general'
        ]);
        echo "Created dummy transaction\n";
    }

    echo "Calling generateCashierPdf...\n";
    // Inline the controller code to see where it hangs
    $userId = $user->id;
    $cashierName = $user->name;
    $request = $req;
    
    $from = $request->from ? \Carbon\Carbon::parse($request->from)->startOfDay() : null;
    $to   = $request->to   ? \Carbon\Carbon::parse($request->to)->endOfDay()     : null;

    $query = \App\Models\Transaction::with('bills')->where('user_id', $userId)->orderBy('created_at');
    if ($from) $query->where('created_at', '>=', $from);
    if ($to)   $query->where('created_at', '<=', $to);

    $txs = $query->get();
    echo "Fetched " . count($txs) . " transactions\n";
    
    $rows = [];
    foreach ($txs as $tx) {
        $rows[] = [
            'id' => $tx->id,
            'date' => $tx->created_at,
            'category' => $tx->category,
            'note' => $tx->note,
            'description' => $tx->description,
            'reference' => $tx->reference,
            'site' => $tx->site ?? 'Pentapure',
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'opening_bal' => 0,
            'closing_bal' => 0,
            'bills' => [],
        ];
    }
    
    $data = [
        'reportId' => 123,
        'generatedOn' => now()->format('d-M-Y H:i:s'),
        'fromDate' => '2024',
        'toDate' => '2025',
        'cashierName' => $cashierName,
        'cashierId' => $userId,
        'accountName' => 'PentaPure',
        'site' => 'All',
        'category' => 'All',
        'rows' => $rows,
        'openingBalance' => 0,
        'closingBalance' => 0,
        'sumIn' => 0,
        'sumOut' => 0,
        'totalRecords' => count($rows),
        'includeBills' => true,
        'showBalance' => true,
        'billPages' => [],
    ];
    
    echo "Generating DomPDF...\n";
    $mainPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-statement', $data);
    $mainPdf->setPaper('A4', 'portrait');
    $mainPdfContent = $mainPdf->output();
    echo "DomPDF generated! Size: " . strlen($mainPdfContent) . "\n";
    
    echo "Done.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
