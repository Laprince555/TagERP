<?php

namespace Modules\General\Livewire\System\Backups;

use App\Models\BackupLog;
use App\Services\NavigationTreeService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class BackupManager extends Component
{
    public bool $isBackingUp = false;

    public function startBackup(): void
    {
        try {
            Artisan::call('app:backup-system', ['--user-id' => auth()->id()]);
            $this->dispatch('backup-completed');
            session()->flash('success', 'Backup created successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Backup failed: '.$e->getMessage());
        }

        $this->isBackingUp = false;
    }

    public function deleteBackup(BackupLog $backup): void
    {
        try {
            if (file_exists($backup->file_path)) {
                unlink($backup->file_path);
            }
            $backup->delete();
            session()->flash('success', 'Backup deleted successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete backup: '.$e->getMessage());
        }
    }

    public function downloadBackup(BackupLog $backup)
    {
        if (file_exists($backup->file_path)) {
            return response()->download($backup->file_path);
        }

        session()->flash('error', 'Backup file not found');
    }

    public function render()
    {
        $context = app(NavigationTreeService::class)->locateApplication('gen-sys-bkp');

        return view('general::livewire.system.backups.backup-manager', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
