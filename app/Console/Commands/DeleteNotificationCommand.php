<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:delete 
                            {search? : Search text in notification message, title, or target user} 
                            {--id= : Delete specific notification by ID} 
                            {--all : Delete all notifications} 
                            {--force : Force the deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notification(s) matching search text, ID, or all notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $search = $this->argument('search');
        $id = $this->option('id');
        $all = $this->option('all');
        $force = $this->option('force');

        if (!$search && !$id && !$all) {
            $this->error('Please provide a search string, an --id, or --all.');
            return 1;
        }

        $query = DB::table('notifications');

        if ($id) {
            $query->where('id', (string)$id)->orWhere('id', (int)$id);
        } elseif ($all) {
            // Delete all
        } elseif ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('data', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No matching notifications found.');
            return 0;
        }

        if (!$force && !$this->confirm("Are you sure you want to delete {$count} notification(s)?", true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $deleted = $query->delete();
        $this->info("Successfully deleted {$deleted} notification(s).");

        return 0;
    }
}
