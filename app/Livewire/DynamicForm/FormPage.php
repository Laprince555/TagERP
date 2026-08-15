<?php

namespace App\Livewire\DynamicForm;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Full-page host for one Dynamic Form — the same definition FormModal
 * hosts in a modal, with zero duplication of field rendering/validate/save
 * logic (that all lives in App\Livewire\DynamicForm\Form).
 */
#[Layout('layouts.app')]
class FormPage extends Component
{
    public string $formKey;

    public function mount(string $formKey): void
    {
        $this->formKey = $formKey;
    }

    public function render(): View
    {
        return view('livewire.dynamic-form.form-page', [
            'definition' => app(FormDefinitionRegistry::class)->resolve($this->formKey),
        ]);
    }
}
