<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class MultipleDefaultTabsException extends DynamicRecordViewException
{
    public static function forSection(string $sectionKey): self
    {
        return new self("Section [{$sectionKey}] has more than one default() tab.");
    }
}
