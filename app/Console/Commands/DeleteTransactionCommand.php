<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteTransactionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashier:delete-entry 
                            {--id= : Specific transaction ID to delete}
                            {--keyword= : Search keyword in note, description, or category (e.g. abc)}
                            {--amount= : Transaction amount (e.g. 100)}
                            {--user= : User name or username (e.g. gulamabbas)}
                            {--force : Force deletion without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and safely delete a specific cashier transaction by ID or criteria (e.g. abc / 100).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        $keyword = $this->option('keyword') ?: 'abc';
        $amount = $this->option('amount') ?: 100;
        $userName = $this->option('user');

        $query = Transaction::with(['user', 'bills']);

        if ($id) {
            $query->where('id', $id);
        } else {
            if ($amount !== null && $amount !== '') {
                $query->where('amount', (float)$amount);
            }

            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('note', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%")
                      ->orWhere('category', 'like', "%{$keyword}%")
                      ->orWhere('reference', 'like', "%{$keyword}%");
                });
            }

            if ($userName) {
                $query->whereHas('user', function($uq) use ($userName) {
                    $uq->where('name', 'like', "%{$userName}%")
                       ->orWhere('username', 'like', "%{$userName}%");
                });
            }
        }

        $transactions = $query->orderByDesc('id')->get();

        if ($transactions->isEmpty()) {
            $this->warn('No matching transactions found with the given criteria.');
            return 1;
        }

        $this->info("Found {$transactions->count()} matching transaction(s):");
        $tableData = [];
        foreach ($transactions as $t) {
            $tableData[] = [
                'ID' => $t->id,
                'Date' => $t->date ?? $t->created_at,
                'User' => $t->user ? $t->user->name : "User #{$t->user_id}",
                'Type' => $t->type,
                'Amount' => '₹' . number_format($t->amount, 2),
                'Category' => $t->category,
                'Note' => $t->note ?: ($t->description ?: '—'),
            ];
        }
        $this->table(['ID', 'Date', 'User', 'Type', 'Amount', 'Category', 'Note'], $tableData);

        if (!$this->option('force') && !$this->confirm('Do you want to permanently delete these transaction(s)?')) {
            $this->warn('Operation aborted.');
            return 0;
        }

        DB::transaction(function() use ($transactions) {
            foreach ($transactions as $tx) {
                // Delete attached bill files
                foreach ($tx->bills as $bill) {
                    if ($bill->file_path && file_exists(storage_path('app/public/' . $bill->file_path))) {
                        @unlink(storage_path('app/public/' . $bill->file_path));
                    }
                }

                $oldData = $tx->toArray();
                $tx->delete();

                TransactionLog::create([
                    'transaction_id' => null,
                    'user_id' => $tx->user_id,
                    'action' => 'DELETED',
                    'old_data' => $oldData,
                    'new_data' => null,
                ]);

                $this->info("✓ Deleted Transaction #{$tx->id} (Amount: ₹{$tx->amount}, Note: {$tx->note})");
            }
        });

        $this->info('✓ Done! Cashier ledger balance will now reflect the removal.');
        return 0;
    }
}
