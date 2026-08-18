<?php

use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalLine;

it('builds the line code from the parent journal via HasAutoLineCode', function (): void {
    $journal = Journal::factory()->create();

    $line = JournalLine::factory()->for($journal, 'journal')->create(['line_number' => 3]);

    expect($line->code)->toBe("{$journal->code}-lin-3");
});

it('does not overwrite an explicitly set code', function (): void {
    $journal = Journal::factory()->create();

    $line = JournalLine::factory()->for($journal, 'journal')->create([
        'line_number' => 1,
        'code' => 'custom-code',
    ]);

    expect($line->code)->toBe('custom-code');
});
