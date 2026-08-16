<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * The authorized record show page for a single JournalBook (fin-gl-bok).
 */
class JournalBookRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.journal-book';

    public function model(): string
    {
        return JournalBook::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JournalBook::query()->whereRaw('1 = 0');
        }

        return JournalBook::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return (string) $record->sequence_prefix;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('basic-information')
                        ->heading('Basic Information')
                        ->fields([
                            TextViewField::make('name')->label('Name'),
                            TextViewField::make('sequence_prefix')->label('Sequence Prefix'),
                            TextViewField::make('description')->label('Description'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
