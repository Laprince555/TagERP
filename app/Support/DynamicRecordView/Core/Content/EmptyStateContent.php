<?php

namespace App\Support\DynamicRecordView\Core\Content;

class EmptyStateContent extends Content
{
    protected string $message = 'Nothing here yet.';

    protected ?string $icon = null;

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
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
}
