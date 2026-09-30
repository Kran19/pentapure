<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAttendanceDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:clear-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all attendance logs, daily submission records, and monthly adjustments while keeping workers, departments, and users 100% intact.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all attendance data? This action is irreversible.')) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $this->info('Disabling foreign key constraints...');
        Schema::disableForeignKeyConstraints();

        $tables = [
            'attendances',
            'attendance_submissions',
            'worker_monthly_adjustments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("Truncating table: <comment>{$table}</comment>...");
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->info('Foreign key constraints re-enabled.');

        $this->newLine();
        $this->info('✓ All attendance records, daily submissions, and monthly adjustments have been successfully cleared.');
        $this->info('✓ All workers (workers table) and departments remain 100% intact.');
        $this->info('✓ All users, products, grades, and inventory remain 100% intact.');

        return 0;
    }
}
