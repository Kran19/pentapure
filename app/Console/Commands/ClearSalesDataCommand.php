<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearSalesDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:clear-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all sales orders, order items, and linked dispatch records while keeping sales users, companies, and transporters intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all sales orders and linked dispatches? This action is irreversible.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        try {
            Schema::disableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (\Throwable $e) {}

        $tables = [
            'dispatch_item_locations',
            'dispatch_log_items',
            'dispatch_logs',
            'order_items',
            'orders',
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

        // Clean up uploaded LR image files (skip in test environment to preserve repo fixtures)
        $lrDir = public_path('lr_images');
        if (!app()->environment('testing') && is_dir($lrDir)) {
            $files = glob($lrDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        $this->newLine();
        $this->info('✓ All sales orders and linked dispatches have been successfully cleared.');
        $this->info('✓ All sales users (users table), companies, and transporters remain 100% intact.');

        return 0;
    }
}
