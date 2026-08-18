<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cleanup-old-backups {--days=30}')]
#[Description('Delete backup files older than the specified number of days')]
class CleanupOldBackups extends Command
{
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $deletedCount = 0;

        BackupLog::where('created_at', '<', $cutoffDate)
            ->where('status', 'success')
            ->lazyById()
            ->each(function (BackupLog $backup) use (&$deletedCount): void {
                try {
                    if (file_exists($backup->file_path)) {
                        unlink($backup->file_path);
                    }
                    $backup->forceDelete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to delete backup {$backup->file_path}: {$e->getMessage()}");
                }
            });

        $this->info("Deleted $deletedCount backup files older than $days days");

        return 0;
    }
}
