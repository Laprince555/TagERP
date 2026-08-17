<?php

namespace App\Support\DynamicRecordView\Core;

/**
 * One button on a Dynamic Record View's header bar.
 *
 * Three shapes, and a definition never needs a fourth:
 *
 * - `url()`      — a plain link out to another screen (the line editor).
 * - `form()`     — opens a Dynamic Form modal over this record (Edit).
 * - otherwise    — calls a handler method on the definition itself
 *                  (delete, post, reverse). A handler that returns a string
 *                  is treated as a URL to redirect to afterwards, which is
 *                  how "reverse" lands on the reversal it just created and
 *                  how "delete" gets back to the index it deleted from.
 *
 * Every action is gated by one Spatie permission, `{applicationCode}.{action}`
 * — exactly the names SyncPermissionsCommand generates from the module tree,
 * so a new button is grantable the moment it is declared. The action name
 * defaults to the button's own key, so `make('post')` checks `…jou.post`;
 * `permission()` overrides it where the two differ (`edit` → `update`).
 * A key that maps to no real permission simply never renders — can() is
 * false for a permission that does not exist, so omission fails closed.
 */
class RecordAction
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected string $variant = 'ghost';

    protected ?string $color = null;

    protected ?string $permissionAction = null;

    protected mixed $visible = true;

    /** @var (callable(mixed): ?string)|null */
    protected $urlUsing = null;

    protected ?string $formKey = null;

    protected bool $copy = false;

    protected ?string $handler = null;

    protected ?string $confirm = null;

    protected ?string $successMessage = null;

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->key)->headline()->toString();
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /** Flux button variant: primary, filled, danger, subtle, ghost. */
    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    /** The action half of the `{applicationCode}.{action}` permission name. */
    public function permission(string $action): static
    {
        $this->permissionAction = $action;

        return $this;
    }

    public function getPermissionAction(): string
    {
        return $this->permissionAction ?? $this->key;
    }

    /**
     * @param  bool|callable(mixed $record): bool  $condition
     */
    public function visible(bool|callable $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function isVisible(mixed $record = null): bool
    {
        return is_callable($this->visible) ? (bool) ($this->visible)($record) : (bool) $this->visible;
    }

    /**
     * @param  callable(mixed $record): ?string  $callback
     */
    public function url(callable $callback): static
    {
        $this->urlUsing = $callback;

        return $this;
    }

    public function getUrl(mixed $record): ?string
    {
        return $this->urlUsing ? ($this->urlUsing)($record) : null;
    }

    /**
     * Opens the registered Dynamic Form for this record in edit mode, or —
     * with $copy — prefilled from this record but pointed at no record at all,
     * so saving creates a new one ("Copy"/"Save as new").
     */
    public function form(string $formKey, bool $copy = false): static
    {
        $this->formKey = $formKey;
        $this->copy = $copy;

        return $this;
    }

    public function getFormKey(): ?string
    {
        return $this->formKey;
    }

    /** Method on the definition to call, defaulting to the action's own key. */
    public function handler(string $method): static
    {
        $this->handler = $method;

        return $this;
    }

    public function getHandler(): string
    {
        return $this->handler ?? $this->key;
    }

    /**
     * Text shown in a confirmation dialog before the handler runs. Only
     * meaningful for handler actions — a link has nothing to confirm.
     */
    public function confirm(string $message): static
    {
        $this->confirm = $message;

        return $this;
    }

    public function getConfirm(): ?string
    {
        return $this->confirm;
    }

    public function successMessage(string $message): static
    {
        $this->successMessage = $message;

        return $this;
    }

    public function getSuccessMessage(): ?string
    {
        return $this->successMessage;
    }

    /** True when this button navigates rather than calling back into the server. */
    public function isLink(): bool
    {
        return $this->urlUsing !== null;
    }

    public function isForm(): bool
    {
        return $this->formKey !== null;
    }

    public function isCopy(): bool
    {
        return $this->copy;
    }

    /**
     * Event/modal namespace for this action's form modal — a copy modal must
     * not answer the edit modal's open event, and vice versa, when both point
     * at the same form key.
     */
    public function getFormModalKey(): string
    {
        return $this->formKey.($this->copy ? '.copy' : '');
    }
}
