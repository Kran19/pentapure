<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CashierController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    // ── HOME ──────────────────────────────────────────────────────────────
    public function home()
    {
        $txs     = Transaction::with('bills')->where('user_id', $this->authUser()['id'])->orderByDesc('created_at')->get();
        $balance = $txs->sum(fn($t) => $t->type === 'IN' ? $t->amount : -$t->amount);

        $pageData = [
            'balance'      => $balance,
            'transactions' => $txs->map(fn($t) => $this->txToArray($t)),
        ];
        return view('cashier.home', compact('pageData'));
    }

    // ── ACTION FORM ────────────────────────────────────────────────────────
    public function action()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $defaultValue = $categories->map(function ($c) {
            return [
                'id'    => $c->id,
                'value' => strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($c->name))),
                'label' => $c->name
            ];
        });

        return view('cashier.action', [
            'pageData' => ['categories' => $defaultValue->values()],
        ]);
    }

    // ── STORE TRANSACTION ──────────────────────────────────────────────────
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:IN,OUT',
            'amount'      => 'required|numeric|min:0.01',
            'bill_file'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $userArray = $this->authUser();
        $userModel = User::find($userArray['id']);

        $tx = \DB::transaction(function() use ($request, $userArray, $userModel) {
            $tx = Transaction::create([
                'user_id'     => $userArray['id'],
                'type'        => $request->type,
                'amount'      => $request->amount,
                'category'    => $request->category ?? 'general',
                'note'        => $request->note,
                'reference'   => $request->reference,
                'site'        => $userModel ? $userModel->branch : null,
                'description' => $request->description,
            ]);

            // Handle optional bill file on creation
            if ($request->hasFile('bill_file')) {
                $this->saveBillFile($request->file('bill_file'), $tx->id, 0);
            }
            return $tx;
        });

        return response()->json(['success' => true, 'message' => "Transaction ({$request->type}) saved!", 'transaction_id' => $tx->id]);
    }

    public function updateTransaction(Request $request, $id)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'category' => 'nullable|string',
            'note'     => 'nullable|string',
        ]);

        $user = $this->authUser();
        $tx = Transaction::where('id', $id)->where('user_id', $user['id'])->firstOrFail();

        $oldData = $tx->toArray();

        \DB::transaction(function() use ($request, $tx, $user, $oldData) {
            $tx->update([
                'amount'   => $request->amount,
                'category' => $request->category ?? $tx->category,
                'note'     => $request->note,
            ]);

            \App\Models\TransactionLog::create([
                'transaction_id' => $tx->id,
                'user_id'        => $user['id'],
                'action'         => 'EDITED',
                'old_data'       => $oldData,
                'new_data'       => $tx->toArray(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaction updated!']);
    }

    public function destroyTransaction($id)
    {
        $user = $this->authUser();
        $tx = Transaction::where('id', $id)->where('user_id', $user['id'])->firstOrFail();

        $oldData = $tx->toArray();

        \DB::transaction(function() use ($tx, $user, $oldData) {
            // Also delete attached bills physically
            foreach ($tx->bills as $bill) {
                if ($bill->file_path && file_exists(storage_path('app/public/' . $bill->file_path))) {
                    @unlink(storage_path('app/public/' . $bill->file_path));
                }
            }

            $tx->delete();

            \App\Models\TransactionLog::create([
                'transaction_id' => null, // Since it's deleted, or keep it but the FK must be nullable
                'user_id'        => $user['id'],
                'action'         => 'DELETED',
                'old_data'       => $oldData,
                'new_data'       => null,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaction deleted!']);
    }

    // ── UPLOAD BILL ────────────────────────────────────────────────────────
    public function uploadBill(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'bill_file'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Ensure transaction belongs to this cashier
        $tx = Transaction::where('id', $request->transaction_id)
            ->where('user_id', $this->authUser()['id'])
            ->firstOrFail();

        $sortOrder = TransactionBill::where('transaction_id', $tx->id)->max('sort_order') + 1;
        $bill = $this->saveBillFile($request->file('bill_file'), $tx->id, $sortOrder);

        return response()->json([
            'success' => true,
            'message' => 'Bill uploaded!',
            'bill' => [
                'id'            => $bill->id,
                'original_name' => $bill->original_name,
                'file_type'     => $bill->file_type,
                'url'           => $bill->url,
            ]
        ]);
    }

    // ── DELETE BILL ────────────────────────────────────────────────────────
    public function destroyBill($id)
    {
        $bill = TransactionBill::with('transaction')->findOrFail($id);

        // Security: only owner can delete
        if ($bill->transaction->user_id !== $this->authUser()['id']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Storage::delete($bill->file_path);
        $bill->delete();

        return response()->json(['success' => true, 'message' => 'Bill deleted.']);
    }

    // ── VIEW / STREAM BILL ─────────────────────────────────────────────────
    public function viewBill($id)
    {
        $bill = TransactionBill::with('transaction')->findOrFail($id);

        $user = $this->authUser();
        if ($user['role'] !== 'ADMIN' && $bill->transaction->user_id !== $user['id']) {
            abort(403);
        }

        $path = Storage::disk('public')->path($bill->file_path);
        if (!file_exists($path)) abort(404);

        return response()->file($path, [
            'Content-Type'        => $bill->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $bill->original_name . '"',
        ]);
    }

    // ── CATEGORY MANAGEMENT ────────────────────────────────────────────────
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);
        
        $category = Category::create([
            'name'      => $request->name,
            'is_active' => true,
        ]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Category created successfully!',
            'category' => [
                'id'    => $category->id,
                'value' => strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($category->name))),
                'label' => $category->name
            ]
        ]);
    }

    public function destroyCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        
        return response()->json(['success' => true, 'message' => 'Category deleted successfully!']);
    }

    // ── HISTORY ────────────────────────────────────────────────────────────
    public function history()
    {
        $txs = Transaction::with('bills')
            ->where('user_id', $this->authUser()['id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => $this->txToArray($t));

        $pageData = ['transactions' => $txs];
        return view('cashier.history', compact('pageData'));
    }

    // ── LEDGER ─────────────────────────────────────────────────────────────
    public function ledger()
    {
        $user = $this->authUser();
        $txs = Transaction::with('bills')->where('user_id', $user['id'])->orderByDesc('created_at')->get();

        $summary = [
            'totalIn'  => $txs->where('type', 'IN')->sum('amount'),
            'totalOut' => $txs->where('type', 'OUT')->sum('amount'),
            'balance'  => $txs->where('type', 'IN')->sum('amount') - $txs->where('type', 'OUT')->sum('amount'),
        ];

        $pageData = [
            'transactions' => $txs->map(fn($t) => $this->txToArray($t)),
            'summary'      => $summary,
        ];

        return view('cashier.ledger', compact('pageData'));
    }

    // ── DOWNLOAD PDF (Enhanced) ────────────────────────────────────────────
    public function downloadPdf(Request $request)
    {
        $user = $this->authUser();
        return $this->generateCashierPdf($request, $user['id'], $user['name']);
    }

    // ── PROFILE ────────────────────────────────────────────────────────────
    public function profile()
    {
        return view('cashier.profile');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SHARED PDF GENERATION (used by cashier & admin)
    // ══════════════════════════════════════════════════════════════════════
    public function generateCashierPdf(Request $request, int $userId, string $cashierName)
    {
        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : null;
        $to   = $request->to   ? Carbon::parse($request->to)->endOfDay()     : null;

        $query = Transaction::with('bills')->where('user_id', $userId)->orderBy('created_at');

        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);
        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->site && $request->site !== 'all') {
            $query->where('site', $request->site);
        }

        $txs = $query->get();

        // Opening balance = sum of all transactions BEFORE the from date
        $openingBalance = $request->has('opening_balance') && $request->opening_balance !== ''
            ? (float) $request->opening_balance
            : 0.0;

        if ($from && !($request->has('opening_balance') && $request->opening_balance !== '')) {
            $prevTxs = Transaction::where('user_id', $userId)->where('created_at', '<', $from)->get();
            $openingBalance = (float) $prevTxs->sum(fn($t) => $t->type === 'IN' ? $t->amount : -$t->amount);
        }

        // Build transaction rows with running balance
        $runningBalance = $openingBalance;
        $rows = [];
        foreach ($txs as $tx) {
            $openBal = $runningBalance;
            if ($tx->type === 'IN') {
                $runningBalance += $tx->amount;
            } else {
                $runningBalance -= $tx->amount;
            }
            $rows[] = [
                'id'          => $tx->id,
                'date'        => $tx->created_at,
                'category'    => $tx->category,
                'note'        => $tx->note,
                'description' => $tx->description,
                'reference'   => $tx->reference,
                'site'        => $tx->site ?? 'Pentapure',
                'type'        => $tx->type,
                'amount'      => (float) $tx->amount,
                'opening_bal' => $openBal,
                'closing_bal' => $runningBalance,
                'bills'       => $tx->bills->map(fn($b) => [
                    'id'            => $b->id,
                    'file_type'     => $b->file_type,
                    'original_name' => $b->original_name,
                    'absolute_path' => $b->absolute_path,
                    'mime_type'     => $b->mime_type,
                ])->toArray(),
            ];
        }

        $sumIn  = $txs->where('type', 'IN')->sum('amount');
        $sumOut = $txs->where('type', 'OUT')->sum('amount');

        $includeBills = $request->include_bills !== 'no';
        $showBalance = $request->show_balance !== 'no';

        $data = [
            'reportId'       => $userId * 100 + rand(1, 99),
            'generatedOn'    => now()->format('d-M-Y H:i:s'),
            'fromDate'       => $from ? $from->format('Y-m-d') : ($txs->first()?->created_at?->format('Y-m-d') ?? now()->format('Y-m-d')),
            'toDate'         => $to   ? $to->format('Y-m-d')   : now()->format('Y-m-d'),
            'cashierName'    => $cashierName,
            'cashierId'      => $userId,
            'accountName'    => 'Pentapure Foods and Spices',
            'site'           => $request->site && $request->site !== 'all' ? $request->site : 'All',
            'category'       => $request->category && $request->category !== 'all' ? ucwords(str_replace('_',' ',$request->category)) : 'All',
            'rows'           => $rows,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'sumIn'          => $sumIn,
            'sumOut'         => $sumOut,
            'totalRecords'   => count($rows),
            'includeBills'   => $includeBills,
            'showBalance'    => $showBalance,
            'billPages'      => [], // no embedded blade bills, using FPDI
        ];

        // 1. Generate the main statement HTML via DomPDF
        $mainPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-statement', $data);
        $mainPdf->setPaper('A4', 'portrait');
        $mainPdfContent = $mainPdf->output();

        // If no bills to include, return immediately
        if (!$includeBills) {
            $filename = 'PentaPure_Statement_' . now()->format('Ymd_His') . '.pdf';
            return response($mainPdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // 2. Collect all bill pages in transaction sequence order
        $billPages = [];
        foreach ($rows as $row) {
            foreach ($row['bills'] as $bill) {
                $billPages[] = array_merge($bill, [
                    'tx_id'     => $row['id'],
                    'tx_date'   => $row['date']->format('d-M-Y'),
                    'tx_cat'    => ucwords(str_replace('_', ' ', $row['category'])),
                    'tx_amount' => ($row['type'] === 'OUT' ? '-' : '+') . '₹' . number_format($row['amount'], 2),
                    'tx_note'   => $row['note'] ?? '',
                ]);
            }
        }

        if (empty($billPages)) {
            $filename = 'PentaPure_Statement_' . now()->format('Ymd_His') . '.pdf';
            return response($mainPdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // 3. Merge using FPDI
        $merged = $this->mergePdfWithBills($mainPdfContent, $billPages);

        $filename = 'PentaPure_Statement_' . now()->format('Ymd_His') . '.pdf';
        return response($merged, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── FPDI MERGER ────────────────────────────────────────────────────────
    private function mergePdfWithBills(string $mainPdfContent, array $billPages): string
    {
        // Write main PDF to a temp file
        $tmpMain = tempnam(sys_get_temp_dir(), 'ppure_main_') . '.pdf';
        file_put_contents($tmpMain, $mainPdfContent);

        // Use FPDI to merge
        $fpdi = new \setasign\Fpdi\Fpdi();
        $fpdi->SetAutoPageBreak(false);

        // Import all pages of the main PDF
        $mainPageCount = $fpdi->setSourceFile($tmpMain);
        for ($i = 1; $i <= $mainPageCount; $i++) {
            $tpl = $fpdi->importPage($i);
            $fpdi->AddPage('P', 'A4');
            $fpdi->useTemplate($tpl, 0, 0, 210, 297);
        }

        // Now add each bill as a new page
        $billNum = 1;
        foreach ($billPages as $bill) {
            $absPath = $bill['absolute_path'];
            if (!file_exists($absPath)) { $billNum++; continue; }

            if ($bill['file_type'] === 'pdf') {
                // Import each page of the bill PDF
                try {
                    $billPageCount = $fpdi->setSourceFile($absPath);
                    for ($p = 1; $p <= $billPageCount; $p++) {
                        $tpl = $fpdi->importPage($p);
                        $fpdi->AddPage('P', 'A4');
                        // Bill header
                        $this->fpdiAddBillHeader($fpdi, $bill, $billNum, count($billPages));
                        $fpdi->useTemplate($tpl, 5, 30, 200, 250);
                    }
                } catch (\Exception $e) {
                    // Skip unreadable PDF
                }
            } else {
                // Image bill
                $fpdi->AddPage('P', 'A4');
                $this->fpdiAddBillHeader($fpdi, $bill, $billNum, count($billPages));
                try {
                    $ext = strtoupper(pathinfo($absPath, PATHINFO_EXTENSION));
                    if ($ext === 'JPG') $ext = 'JPEG';
                    // Calculate fit dimensions maintaining aspect ratio
                    [$imgW, $imgH] = @getimagesize($absPath) ?: [210, 297];
                    $maxW = 200;
                    $maxH = 250;
                    $ratio = min($maxW / $imgW, $maxH / $imgH);
                    $drawW = $imgW * $ratio;
                    $drawH = $imgH * $ratio;
                    $x = (210 - $drawW) / 2;
                    $fpdi->Image($absPath, $x, 35, $drawW, $drawH, $ext);
                } catch (\Exception $e) {
                    $fpdi->SetFont('Helvetica', '', 10);
                    $fpdi->SetXY(10, 50);
                    $fpdi->Cell(0, 10, 'Could not load image: ' . $bill['original_name']);
                }
            }
            $billNum++;
        }

        @unlink($tmpMain);

        return $fpdi->Output('', 'S');
    }

    private function fpdiAddBillHeader(\setasign\Fpdi\Fpdi $fpdi, array $bill, int $num, int $total): void
    {
        // Dark header bar
        $fpdi->SetFillColor(26, 39, 68);
        $fpdi->Rect(0, 0, 210, 24, 'F');

        $fpdi->SetFont('Helvetica', 'B', 11);
        $fpdi->SetTextColor(255, 255, 255);
        $fpdi->SetXY(5, 5);
        $fpdi->Cell(140, 6, strtoupper('PentaPure - Bill Attachment'));

        $fpdi->SetFont('Helvetica', '', 8);
        $fpdi->SetXY(5, 13);
        $fpdi->Cell(60, 5, strtoupper('Txn #' . $bill['tx_id'] . ' | ' . $bill['tx_date'] . ' | ' . $bill['tx_cat']));
        $fpdi->SetXY(75, 13);
        $fpdi->Cell(60, 5, strtoupper('Amount: ' . $bill['tx_amount']));

        // Bill number top-right
        $fpdi->SetFont('Helvetica', 'B', 9);
        $fpdi->SetXY(155, 8);
        $fpdi->Cell(50, 6, strtoupper('Bill ' . $num . ' of ' . $total), 0, 0, 'R');

        // Reset text color
        $fpdi->SetTextColor(0, 0, 0);
    }

    // ── HELPERS ────────────────────────────────────────────────────────────
    private function saveBillFile($file, int $txId, int $sortOrder): TransactionBill
    {
        $mime = $file->getMimeType();
        $type = str_starts_with($mime, 'image/') ? 'image' : 'pdf';
        $ext  = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $path = $file->storeAs("bills/{$txId}", uniqid() . '.' . $ext, 'public');

        return TransactionBill::create([
            'transaction_id' => $txId,
            'file_path'      => $path,
            'file_type'      => $type,
            'original_name'  => $name,
            'mime_type'      => $mime,
            'file_size'      => $file->getSize(),
            'sort_order'     => $sortOrder,
        ]);
    }

    private function txToArray(Transaction $t): array
    {
        return [
            'id'          => $t->id,
            'type'        => $t->type,
            'amount'      => $t->amount,
            'category'    => $t->category,
            'note'        => $t->note,
            'reference'   => $t->reference,
            'site'        => $t->site,
            'description' => $t->description,
            'date'        => $t->created_at->toISOString(),
            'bills'       => $t->bills->map(fn($b) => [
                'id'            => $b->id,
                'original_name' => $b->original_name,
                'file_type'     => $b->file_type,
                'url'           => $b->url,
            ])->toArray(),
        ];
    }
}
