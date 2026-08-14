<?php

namespace App\Support\DynamicRecordView\Core\Fields;

class LinkViewField extends Field
{
    /** @var (callable(mixed): ?string)|null */
    protected $linkUsing = null;

    protected bool $openInNewTab = false;

    /**
     * @param  callable(mixed $record): ?string  $callback
     */
    public function linkUsing(callable $callback): static
    {
        $this->linkUsing = $callback;

        return $this;
    }

    /** Schemes allowed to render as a clickable link; anything else (javascript:, data:, vbscript:, ...) is dropped. */
    protected const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function getUrl(mixed $record): ?string
    {
        $url = $this->linkUsing ? ($this->linkUsing)($record) : null;

        return $url !== null && self::isSafeUrl($url) ? $url : null;
    }

    /**
     * Relative/internal URLs (no scheme, e.g. "/customers/1") are allowed.
     * Absolute URLs must use an allowed scheme.
     */
    public static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x1f]/', $url)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === null || in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true);
    }

    public function openInNewTab(bool $newTab = true): static
    {
        $this->openInNewTab = $newTab;

        return $this;
    }

    public function opensInNewTab(): bool
    {
        return $this->openInNewTab;
    }
}
