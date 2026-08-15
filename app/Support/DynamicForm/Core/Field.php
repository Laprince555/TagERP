<?php

namespace App\Support\DynamicForm\Core;

/**
 * Framework-facing field contract. MUST NOT import any Modules\* class or
 * concrete application Eloquent model — mirrors App\Support\DynamicTable\Core\Column.
 */
abstract class Field
{
    protected ?string $label = null;

    protected bool $required = false;

    protected ?string $placeholder = null;

    protected ?string $helpText = null;

    /** @var string[] additional Laravel validation rules, beyond required(). */
    protected array $rules = [];

    protected function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function helpText(string $text): static
    {
        $this->helpText = $text;

        return $this;
    }

    /** @param  string[]  $rules */
    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->key)->headline()->toString();
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    /** @return string[] full validation rule set for this field (required + custom). */
    public function getRules(): array
    {
        return [
            ...($this->required ? ['required'] : ['nullable']),
            ...$this->rules,
        ];
    }

    /** Blade component name rendering this field, e.g. "dynamic-form.fields.text". */
    abstract public function component(): string;

    /**
     * Final, order-independent configuration validation, called once by
     * FormDefinition after every field's fluent chain has already run.
     */
    public function validate(): void {}
}
