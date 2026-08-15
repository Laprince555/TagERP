<?php

namespace App\Livewire\DynamicForm;

use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Flux modal host for one Dynamic Form. Rendered once per Dynamic Table
 * that declares createForm() (see App\Livewire\DynamicTable\Table and
 * resources/views/livewire/dynamic-table/table.blade.php). Opened by the
 * table's toolbar "Create" button dispatching a scoped browser event —
 * mirrors App\Livewire\DynamicRecordView\RelationPickerModal exactly.
 */
class FormModal extends Component
{
    #[Locked]
    public string $formKey;

    /** Modal title — normally the same wording as the table button that opens it. */
    #[Locked]
    public string $heading = '';

    public function mount(string $formKey, string $heading = ''): void
    {
        $this->formKey = $formKey;
        $this->heading = $heading;
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'open-form-modal.'.$this->formKey => 'openForm',
            'dynamic-form-saved.'.$this->formKey => 'closeAfterSave',
        ];
    }

    /**
     * The toolbar button's $dispatch only reaches this component because of
     * the server-side listener above; $wire.on() in the view listens for
     * events this component dispatches, not for a sibling's client-side
     * $dispatch. So opening is a two-step relay — button event in,
     * form-modal-opened out — exactly like RelationPickerModal::openPicker().
     * Without the relay the button silently does nothing.
     */
    public function openForm(): void
    {
        $this->dispatch('form-modal-opened.'.$this->formKey);
    }

    public function closeAfterSave(): void
    {
        $this->dispatch('close-form-modal.'.$this->formKey);
    }

    public function render()
    {
        return view('livewire.dynamic-form.form-modal', [
            'heading' => $this->heading !== '' ? $this->heading : __('Create'),
        ]);
    }
}
