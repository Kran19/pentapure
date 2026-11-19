<?php

namespace App\Console\Commands;

use App\Http\Controllers\DispatchController;
use App\Models\DispatchLog;
use Illuminate\Console\Command;

class SyncLrBackups extends Command
{
    protected $signature = 'lr:sync-backups';
    protected $description = 'Synchronize LR images bidirectionally between disk folders and MySQL dispatch_lr_backups table.';

    public function handle(): int
    {
        $backedUp = 0;
        $healed = 0;
        $missing = 0;

        $logs = DispatchLog::whereNotNull('lr_image_path')->orderBy('id')->get();
        foreach ($logs as $log) {
            foreach (DispatchController::getLrImagePaths($log) as $rp) {
                // resolveLocalFilePath automatically self-heals from DB if missing on disk!
                $hadDiskDirect = DispatchController::findExistingDiskFileOnly($rp);
                $resolved = DispatchController::resolveLocalFilePath($rp);

                if ($resolved && file_exists($resolved)) {
                    if (!$hadDiskDirect) {
                        $healed++;
                    }
                    $rawBytes = @file_get_contents($resolved);
                    if ($rawBytes && strlen($rawBytes) >= 20) {
                        DispatchController::writeBinaryToLrDirectories(basename($rp), $rawBytes);
                        if (DispatchController::storeLrDbBackup($rp, $rawBytes, $log->id)) {
                            $backedUp++;
                        }
                    }
                } else {
                    $missing++;
                }
            }
        }

        $this->info("LR Backup Sync Complete: {$backedUp} synced to DB/disk, {$healed} self-healed from DB, {$missing} historical files missing.");
        return self::SUCCESS;
    }
}
