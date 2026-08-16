<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * What a named set of accounts is for. Only Access groups affect what anybody
 * can see; Template groups are a convenience for filling charts.
 */
enum AccountGroupPurpose: string
{
    case Access = 'access';
    case Template = 'template';

    public function label(): string
    {
        return match ($this) {
            self::Access => __('Access'),
            self::Template => __('Template'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $purpose) {
            $options[$purpose->value] = $purpose->label();
        }

        return $options;
    }
}
