<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearCashierDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashier:clear-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all cashier transactions, bills, and logs while keeping cashier users and categories intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all cashier transactions? This action is irreversible.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        Schema::disableForeignKeyConstraints();

        $tables = [
            'transaction_bills',
            'transaction_logs',
            'transactions',
        ];

        foreach ($tables as $table) {
            $this->line("Truncating table: <comment>{$table}</comment>...");
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
        $this->info('Foreign key constraints re-enabled.');

        $this->newLine();
        $this->info('✓ All cashier transactions, bills, and audit logs have been successfully cleared.');
        $this->info('✓ All cashier users (users table) and expense categories remain 100% intact.');

        return 0;
    }
}
