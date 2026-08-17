<?php

namespace App\Livewire\Imports;

use App\Models\Import;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Progress page for one queued import, reached from its notification. Thin
 * page shell only — the line-by-line results live in ImportRowsTable.
 */
#[Layout('layouts.app')]
class ImportPage extends Component
{
    #[Locked]
    public int $importId;

    public function mount(int $import): void
    {
        $this->importId = $import;

        $this->import();
    }

    /**
     * Ownership is the whole gate, and it is re-asked on every render — an
     * import belongs to the person who uploaded it and nobody else.
     */
    protected function import(): Import
    {
        $import = Import::query()
            ->whereKey($this->importId)
            ->where('user_id', auth()->id())
            ->first();

        if ($import === null) {
            throw new NotFoundHttpException;
        }

        return $import;
    }

    /** Nudges the nested rows table while the job is still running. */
    public function pollProgress(): void
    {
        $this->dispatch('refresh-import-rows');
    }

    /**
     * "I have seen the results" — drops the uploaded file. Re-fetches through
     * import() so the ownership check runs again on the action itself, not
     * only on the render that drew the button.
     */
    public function acknowledge(): void
    {
        $this->import()->acknowledge();

        Flux::toast(
            text: __('The uploaded file has been deleted. The results below are kept.'),
            heading: __('Import acknowledged'),
            variant: 'success',
        );
    }

    public function render(): View
    {
        return view('livewire.imports.import-page', [
            'import' => $this->import(),
        ]);
    }
}
