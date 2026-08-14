<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

class NoAuthorizedDefaultTabException extends DynamicRecordViewException
{
    public static function forSection(string $sectionKey): self
    {
        return new self("Section [{$sectionKey}] has no visible/authorized tab to use as the active tab.");
    }
}
