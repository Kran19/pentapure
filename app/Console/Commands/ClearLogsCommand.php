<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear-all {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all system activity logs while keeping stock inventory balances, users, and products intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all system activity logs? This action is irreversible.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        try {
            Schema::disableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (\Throwable $e) {}

        $tables = [
            'production_log_inputs',
            'production_logs',
            'transaction_logs',
            'notifications',
            'dispatch_logs',
            'dispatch_log_items',
            'dispatch_item_locations',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("Truncating table: <comment>{$table}</comment>...");
                try {
                    DB::table($table)->truncate();
                } catch (\Throwable $e) {
                    DB::table($table)->delete();
                }
            }
        }

        try {
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {}
        $this->info('Foreign key constraints re-enabled.');

        // Record persistent cutoff timestamp so dynamic activity logs start fresh
        \App\Http\Controllers\AdminController::setLogsClearedAt();

        $this->newLine();
        $this->info('✓ System activity logs (https://pentapureadmin.in/public/admin/logs) have been successfully cleared.');
        $this->info('✓ Current inventory stock balances (stocks table) remain 100% intact.');
        $this->info('✓ Users, products, and grades remain 100% intact.');

        return 0;
    }
}
