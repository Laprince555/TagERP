<?php

namespace Modules\Inventory\System\Warehousing;

/**
 * Display-only companion to `cycle_counts.status` (a plain DB enum column —
 * CycleCount itself is out of scope and carries no cast/enum for it). Used
 * only where the UI engines require a real BackedEnum (EnumColumn/EnumFilter/
 * EnumViewField); business logic in CycleCountRecordView compares against the
 * raw string values directly, same as the column always has.
 */
enum CycleCountStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case VarianceApplied = 'variance_applied';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::VarianceApplied => 'Variance Applied',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
