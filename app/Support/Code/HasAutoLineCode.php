<?php

namespace App\Support\Code;

use Modules\General\System\SubApplication;

/**
 * Auto-generates the hierarchical `code` for a line model on create, as
 * "{parentCode}-{slug}-{line_number}" (see .ai/rules/code-field-hierarchy.md).
 * Any Lines model (journal lines, purchase invoice lines, ...) mixes this in
 * instead of hand-rolling the same creating() hook. Also stamps
 * `sub_application_id`, resolved from the registered SubApplication whose
 * code is "{parentApplicationCode}-{slug}" — every line type must be
 * registered there (see each module's Database/Seeders/SubApplicationsSeeder.php).
 */
trait HasAutoLineCode
{
    protected static function bootHasAutoLineCode(): void
    {
        static::creating(function ($line): void {
            $parent = $line->{$line->lineParentRelationName()};

            if (empty($line->code)) {
                $line->code = app(RecordCodeBuilder::class)->subApplicationRecordCode(
                    (string) $parent?->code,
                    $line->lineCodeSlug(),
                    (string) $line->line_number,
                );
            }

            if (! $line->sub_application_id && $line->isFillable('sub_application_id') && $parent) {
                $line->sub_application_id = SubApplication::where(
                    'code',
                    $parent::APPLICATION_CODE.'-'.$line->lineCodeSlug(),
                )->value('id');
            }
        });
    }

    /** Name of the BelongsTo relation to the parent record (e.g. 'journal'). */
    abstract protected function lineParentRelationName(): string;

    /** Slug segment for this line type in the code hierarchy (e.g. 'lin'). */
    abstract protected function lineCodeSlug(): string;
}
