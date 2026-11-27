<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransactionBill;

class FindBillsCommand extends Command
{
    protected $signature = 'cashier:find-bills';
    protected $description = 'Search the entire server for old bill image files and restore them';

    public function handle()
    {
        $this->info("=== Searching for Old Bill Images on Server ===");
        
        $bills = TransactionBill::all();
        $total = $bills->count();

        if ($total === 0) {
            $this->warn("No bill records found in database.");
            return 0;
        }

        $this->line("Database has <comment>{$total}</comment> bill records to locate.");

        // Potential search locations across Hostinger
        $searchDirs = [
            '/home/backup-works',
            '/home/backup-works/pentapure/storage/app/public/bills',
            '/home/backup-works/public_html/storage/bills',
            '/home/u192814865/pentapure/storage/app/public/bills',
            '/home/u192814865/public_html/storage/bills',
            '/home/u192814865/public_html/bills',
            '/home/u192814865/domains/pentapureadmin.in/public_html/storage/bills',
            '/home/u192814865/domains/pentapureadmin.in/public_html/bills',
            '/home/u192814865/.snapshot',
            '/home/u192814865/.trash',
            '/home/u192814865/backup',
            '/home/u192814865/backups',
            '/home/u192814865',
            base_path('..'),
            base_path('../..'),
        ];

        // 1. First, search for any directories named "bills"
        $discoveredBillDirs = [];
        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $discoveredBillDirs[] = $dir;
                // Check immediate subdirectories
                $subdirs = @glob($dir . '/*', GLOB_ONLYDIR) ?: [];
                foreach ($subdirs as $s) {
                    if (basename($s) === 'bills' || str_contains($s, 'storage')) {
                        $discoveredBillDirs[] = $s;
                    }
                }
            }
        }
        $discoveredBillDirs = array_unique($discoveredBillDirs);

        $recovered = 0;
        $alreadyOk = 0;
        $stillMissing = 0;

        foreach ($bills as $bill) {
            $expectedRel = ltrim(preg_replace('/^(public[\/\\\\]|storage[\/\\\\])+/i', '', trim($bill->file_path)), '/\\');
            $targetDest = storage_path('app/public/' . $expectedRel);
            $targetDir  = dirname($targetDest);
            $basename   = basename($expectedRel);

            // Check if already in destination
            if (file_exists($targetDest) && is_file($targetDest) && filesize($targetDest) > 0) {
                $alreadyOk++;
                $this->line("  [EXISTS] ID #{$bill->id}: {$expectedRel}");
                continue;
            }

            // Search in discovered directories
            $foundSource = null;

            // Direct check with expected relative path
            foreach ($discoveredBillDirs as $bDir) {
                $checkPath1 = $bDir . '/' . $expectedRel;
                $checkPath2 = $bDir . '/' . $basename;
                $checkPath3 = $bDir . '/' . $bill->transaction_id . '/' . $basename;

                if (file_exists($checkPath1) && is_file($checkPath1) && filesize($checkPath1) > 0) {
                    $foundSource = $checkPath1;
                    break;
                }
                if (file_exists($checkPath2) && is_file($checkPath2) && filesize($checkPath2) > 0) {
                    $foundSource = $checkPath2;
                    break;
                }
                if (file_exists($checkPath3) && is_file($checkPath3) && filesize($checkPath3) > 0) {
                    $foundSource = $checkPath3;
                    break;
                }
            }

            if ($foundSource) {
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                if (@copy($foundSource, $targetDest)) {
                    @chmod($targetDest, 0664);
                    $recovered++;
                    $this->info("  [RECOVERED] ID #{$bill->id}: Found at {$foundSource} -> Copied to {$targetDest}");
                } else {
                    $this->warn("  [FOUND BUT FAILED TO COPY] {$foundSource}");
                }
            } else {
                $stillMissing++;
                $this->error("  [NOT ON DISK] ID #{$bill->id} (Tx #{$bill->transaction_id}): {$expectedRel} ({$bill->original_name})");
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->line("  Already in place:  <info>{$alreadyOk}</info>");
        $this->line("  Newly recovered:   <info>{$recovered}</info>");
        $this->line("  Still missing:     <error>{$stillMissing}</error>");

        // Ensure storage symlink
        $publicStorage = public_path('storage');
        $publicHtmlStorage = base_path('../public_html/storage');
        if (!file_exists($publicHtmlStorage) && is_dir(base_path('../public_html'))) {
            @symlink(storage_path('app/public'), $publicHtmlStorage);
            $this->info("Created symlink: {$publicHtmlStorage} -> " . storage_path('app/public'));
        }

        if ($stillMissing > 0) {
            $this->newLine();
            $this->warn("=== WHERE TO GET THE MISSING FILES ===");
            $this->line("The database has records for these {$stillMissing} files, but they are not anywhere in the current directories on this server.");
            $this->line("They are stored safely in Hostinger's Automated Daily Backups.");
            $this->newLine();
            $this->comment("HOW TO RESTORE IN 2 MINUTES FROM HOSTINGER:");
            $this->line("1. Open https://hpanel.hostinger.com");
            $this->line("2. Go to: Websites -> Manage (pentapureadmin.in) -> Backups");
            $this->line("3. Click 'File Backups'");
            $this->line("4. Select date: Choose 25-Nov or 26-Nov (before the wipe)");
            $this->line("5. Navigate the folders to find `storage/app/public/bills` (or `public_html/storage/bills`)");
            $this->line("6. Click 'Restore files' (or 'Download files')");
            $this->line("Once restored, run `php artisan cashier:find-bills` again!");
        } else {
            $this->info("All bill images are restored and ready!");
        }

        return 0;
    }
}
