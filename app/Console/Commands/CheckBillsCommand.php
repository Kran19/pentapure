<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransactionBill;
use App\Models\Transaction;

class CheckBillsCommand extends Command
{
    protected $signature = 'cashier:check-bills';
    protected $description = 'Check integrity of transaction bill images on disk';

    public function handle()
    {
        $this->info("Checking Transaction Bills in database and on server disk...");
        $totalBills = TransactionBill::count();
        $totalTransactions = Transaction::count();

        $this->line("Total transactions in database: <comment>{$totalTransactions}</comment>");
        $this->line("Total bill records in database: <comment>{$totalBills}</comment>");

        if ($totalBills === 0) {
            $this->warn("No bill records found in transaction_bills table.");
            return 0;
        }

        $found = 0;
        $missing = 0;

        $bills = TransactionBill::with('transaction')->orderBy('id')->get();
        $rows = [];

        foreach ($bills as $bill) {
            $resolvedPath = TransactionBill::resolveDiskPath($bill->file_path);
            if ($resolvedPath && file_exists($resolvedPath)) {
                $found++;
                $status = '<info>FOUND</info>';
                $location = basename(dirname($resolvedPath)) . '/' . basename($resolvedPath);
            } else {
                $missing++;
                $status = '<error>MISSING ON DISK</error>';
                $location = $bill->file_path;
            }

            $rows[] = [
                $bill->id,
                $bill->transaction_id,
                $bill->original_name,
                $status,
                $location
            ];
        }

        $this->table(['Bill ID', 'Tx ID', 'Original Name', 'Status', 'Resolved / Expected Path'], $rows);

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Found on server disk: <info>{$found}</info>");
        $this->line("  Missing from disk:    <error>{$missing}</error>");

        if ($missing > 0) {
            $this->newLine();
            $this->warn("WHY ARE FILES MISSING?");
            $this->line("The database contains records of uploaded bills, but the physical files in `storage/app/public/bills/` were erased when the server home directory was wiped.");
            $this->newLine();
            $this->comment("HOW TO RESTORE IN 1 MINUTE:");
            $this->line("1. Open Hostinger hPanel -> Files -> Backups.");
            $this->line("2. Click 'File Backups' (choose a date from 1-2 days ago).");
            $this->line("3. Select folder: pentapure/storage/app/public/bills/ and click 'Restore Files'.");
            $this->line("4. Make sure public storage symlink exists:");
            $this->line("   ln -sfn /home/u192814865/pentapure/storage/app/public /home/u192814865/public_html/storage");
        } else {
            $this->info("All bill files are physically present on server disk!");
        }

        return 0;
    }
}
