<?php

namespace App\Support\DynamicRecordView\Core;

use App\Support\DynamicRecordView\Core\Exceptions\DuplicateTabKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\MultipleDefaultTabsException;
use App\Support\DynamicRecordView\Core\Exceptions\NoAuthorizedDefaultTabException;

/**
 * A generic container of RecordTabs (e.g. the Primary section, or the Other
 * Data section). Framework-agnostic — knows nothing about Livewire.
 */
class RecordSection
{
    /** @var array<string, RecordTab> */
    protected array $tabs = [];

    protected ?string $label = null;

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

    /**
     * @param  RecordTab[]  $tabs
     */
    public function tabs(array $tabs): static
    {
        $defaults = 0;

        foreach ($tabs as $tab) {
            if (isset($this->tabs[$tab->getKey()])) {
                throw DuplicateTabKeyException::forKey($tab->getKey());
            }

            if ($tab->isDefault()) {
                $defaults++;
            }

            $this->tabs[$tab->getKey()] = $tab;
        }

        if ($defaults > 1) {
            throw MultipleDefaultTabsException::forSection($this->key);
        }

        return $this;
    }

    /**
     * @return RecordTab[]
     */
    public function getTabs(): array
    {
        return array_values($this->tabs);
    }

    /**
     * Tabs currently visible/authorized for $record, in declaration order.
     *
     * @return RecordTab[]
     */
    public function authorizedTabs(mixed $record = null): array
    {
        return array_values(array_filter($this->tabs, fn (RecordTab $tab) => $tab->isVisible($record)));
    }

    /**
     * The tab key to activate by default: the explicit default() tab if it's
     * authorized, otherwise the first authorized tab. Throws if nothing is
     * authorized at all — a section with tabs must always have somewhere to land.
     */
    public function defaultTabKey(mixed $record = null): string
    {
        $authorized = $this->authorizedTabs($record);

        if ($authorized === []) {
            throw NoAuthorizedDefaultTabException::forSection($this->key);
        }

        $explicit = collect($authorized)->first(fn (RecordTab $tab) => $tab->isDefault());

        return ($explicit ?? $authorized[0])->getKey();
    }

    /**
     * Maximum length accepted for a candidate tab key before even attempting
     * a lookup — guards against arbitrarily long client-supplied strings.
     */
    public const MAX_TAB_KEY_LENGTH = 100;

    /**
     * Resolves a client-supplied candidate tab key to a safe, currently
     * authorized tab key: the candidate itself only if it is both defined
     * and authorized for $record, otherwise the default authorized tab.
     * Oversized or unknown/unauthorized candidates never reach the lookup —
     * this is the only path RecordView/OtherData should use to accept
     * browser-supplied active-tab input.
     */
    public function normalizeActiveTabKey(?string $candidate, mixed $record = null): string
    {
        if ($candidate !== null && $candidate !== '' && mb_strlen($candidate) <= self::MAX_TAB_KEY_LENGTH) {
            $authorized = $this->authorizedTabs($record);
            $match = collect($authorized)->first(fn (RecordTab $tab) => $tab->getKey() === $candidate);

            if ($match !== null) {
                return $match->getKey();
            }
        }

        return $this->defaultTabKey($record);
    }
}
