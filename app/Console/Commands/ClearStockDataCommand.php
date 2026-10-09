<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearStockDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:clear-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all live inventory stock transactions and balances (resetting stock to 0) while keeping products, grades, and locations intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all inventory stock data? This action will reset all live stock quantities to zero.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        try {
            Schema::disableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (\Throwable $e) {}

        if (Schema::hasTable('stocks')) {
            $this->line("Truncating table: <comment>stocks</comment>...");
            try {
                DB::table('stocks')->truncate();
            } catch (\Throwable $e) {
                DB::table('stocks')->delete();
            }
        }

        try {
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {}
        $this->info('Foreign key constraints re-enabled.');

        $this->newLine();
        $this->info('✓ All inventory stock data has been successfully cleared (Live Stock reset to 0).');
        $this->info('✓ All products, grades, and locations remain 100% intact.');

        return 0;
    }
}
