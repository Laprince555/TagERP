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

    /**
     * Set only when this modal edits an existing record (the Edit action on a
     * Dynamic Record View). Null is the create case every table uses.
     */
    #[Locked]
    public int|string|null $recordId = null;

    /** Prefill from $recordId but save as a new record (the Copy action). */
    #[Locked]
    public bool $copy = false;

    public function mount(string $formKey, string $heading = '', int|string|null $recordId = null, bool $copy = false): void
    {
        $this->formKey = $formKey;
        $this->heading = $heading;
        $this->recordId = $recordId;
        $this->copy = $copy;
    }

    /**
     * Own namespace for a copy modal, so Edit and Copy over the same form key
     * are two dialogs that open independently rather than both at once.
     */
    public function modalKey(): string
    {
        return $this->formKey.($this->copy ? '.copy' : '');
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'open-form-modal.'.$this->modalKey() => 'openForm',
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
        $this->dispatch('form-modal-opened.'.$this->modalKey());
    }

    public function closeAfterSave(): void
    {
        $this->dispatch('close-form-modal.'.$this->modalKey());
    }

    public function render()
    {
        return view('livewire.dynamic-form.form-modal', [
            'heading' => $this->heading !== '' ? $this->heading : __('Create'),
            'modalKey' => $this->modalKey(),
        ]);
    }
}
