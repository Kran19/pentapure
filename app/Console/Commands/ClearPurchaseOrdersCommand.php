<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearPurchaseOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:clear-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all purchase orders (PO Received by System) while keeping users and products intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all purchase orders? This action is irreversible.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        Schema::disableForeignKeyConstraints();

        $this->line('Truncating table: <comment>purchase_orders</comment>...');
        DB::table('purchase_orders')->truncate();

        Schema::enableForeignKeyConstraints();
        $this->info('Foreign key constraints re-enabled.');

        $this->newLine();
        $this->info('✓ All purchase orders have been successfully cleared.');
        $this->info('✓ All users, products, and grades remain 100% intact.');

        return 0;
    }
}
