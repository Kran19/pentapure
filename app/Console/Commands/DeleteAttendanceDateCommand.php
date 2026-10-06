<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteAttendanceDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:delete-date {date : The attendance date to delete (e.g. 2026-10-01 or 01-10-2026)} {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all attendance punches and daily submission records for a specific date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawDate = $this->argument('date');
        try {
            $date = Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->error("Invalid date format provided: {$rawDate}");
            return 1;
        }

        $punchCount = Attendance::where(function ($q) use ($date, $rawDate) {
            $q->whereDate('date', $date)
              ->orWhere('date', $date)
              ->orWhere('date', $rawDate)
              ->orWhere('date', 'like', "{$date}%");
        })->count();

        $subCount = AttendanceSubmission::where(function ($q) use ($date, $rawDate) {
            $q->whereDate('attendance_date', $date)
              ->orWhere('attendance_date', $date)
              ->orWhere('attendance_date', $rawDate)
              ->orWhere('attendance_date', 'like', "{$date}%");
        })->count();

        $formattedDisplay = Carbon::parse($date)->format('d-m-Y');

        $this->line("Found <comment>{$punchCount}</comment> punch record(s) and <comment>{$subCount}</comment> submission record(s) for <comment>{$formattedDisplay} ({$date})</comment>.");

        if (!$this->option('force') && !$this->confirm("Are you sure you want to delete attendance data for {$formattedDisplay} ({$date})? This action cannot be undone.")) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $deletedPunches = Attendance::where(function ($q) use ($date, $rawDate) {
            $q->whereDate('date', $date)
              ->orWhere('date', $date)
              ->orWhere('date', $rawDate)
              ->orWhere('date', 'like', "{$date}%");
        })->delete();

        $deletedSub = AttendanceSubmission::where(function ($q) use ($date, $rawDate) {
            $q->whereDate('attendance_date', $date)
              ->orWhere('attendance_date', $date)
              ->orWhere('attendance_date', $rawDate)
              ->orWhere('attendance_date', 'like', "{$date}%");
        })->delete();

        $this->info("✓ Successfully deleted {$deletedPunches} attendance punch(es) and {$deletedSub} submission(s) for {$formattedDisplay} ({$date}).");

        return 0;
    }
}
