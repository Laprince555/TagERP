<?php

namespace Modules\HR\Models\Cycles;

/**
 * What kind of circumstance a CycleException authorizes bypassing/delegating
 * a stage for.
 */
enum CycleExceptionType: string
{
    case Value = 'value';
    case Time = 'time';
    case Necessity = 'necessity';
    case DocumentAbsence = 'document_absence';

    public function label(): string
    {
        return match ($this) {
            self::Value => __('Value'),
            self::Time => __('Time'),
            self::Necessity => __('Necessity'),
            self::DocumentAbsence => __('Document Absence'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
