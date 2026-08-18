<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

#[Signature('app:backup-system {--user-id=}')]
#[Description('Backup the entire system database and storage')]
class BackupSystem extends Command
{
    public function handle(): int
    {
        try {
            $userId = $this->option('user-id');
            if (! $userId) {
                $userId = Auth::id() ?? User::first()?->id;
            }

            $backupPath = $this->createBackup();
            $fileSize = filesize($backupPath);

            BackupLog::create([
                'user_id' => $userId ?? 1,
                'file_path' => $backupPath,
                'file_size' => $fileSize,
                'status' => 'success',
            ]);

            $this->info("Backup created successfully at: $backupPath");

            return 0;
        } catch (\Exception $e) {
            $userId = $this->option('user-id') ?? Auth::id() ?? 1;

            BackupLog::create([
                'user_id' => $userId,
                'file_path' => '',
                'status' => 'failure',
                'error_message' => $e->getMessage(),
            ]);

            $this->error('Backup failed: '.$e->getMessage());

            return 1;
        }
    }

    private function createBackup(): string
    {
        $backupDir = storage_path('backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup-'.now()->format('Y-m-d-His').'.zip';
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create zip file');
        }

        // Backup database
        $this->backupDatabase($zip);

        // Backup storage
        $this->backupDirectory($zip, storage_path('app'), 'storage');

        $zip->close();

        return $backupPath;
    }

    private function backupDatabase(ZipArchive $zip): void
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $dumpFile = storage_path('temp-dump.sql');

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($dumpFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database dump failed');
        }

        $zip->addFile($dumpFile, 'database.sql');
        unlink($dumpFile);
    }

    private function backupDirectory(ZipArchive $zip, string $dirPath, string $zipPrefix): void
    {
        if (! is_dir($dirPath)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPrefix.DIRECTORY_SEPARATOR.substr($filePath, strlen($dirPath) + 1);
                $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
            }
        }
    }
}
