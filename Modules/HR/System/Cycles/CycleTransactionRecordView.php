<?php

namespace Modules\HR\System\Cycles;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateTimeViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionLinesTable;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionStatus;

/**
 * The authorized record show page for a single CycleTransaction (hr-cyc-trx).
 * Stages are shown read-only here; approving/rejecting them happens on the
 * dedicated CycleTransactionReview screen via the "Review" action.
 */
class CycleTransactionRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.cycles.transaction';

    public function model(): string
    {
        return CycleTransaction::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleTransaction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CycleTransaction::query()->whereRaw('1 = 0');
        }

        return CycleTransaction::query()->with('cycle', 'subject', 'startedBy');
    }

    public function title(mixed $record): string
    {
        return (string) $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->status->label();
    }

    public function actions(): array
    {
        return [
            RecordAction::make('review')
                ->label('Review')
                ->icon('check-badge')
                ->permission('update')
                ->variant('primary')
                ->url(fn (mixed $record): string => route('hr.cycles.transactions.review', ['recordId' => $record->getKey()]))
                ->visible(fn (mixed $record): bool => $record instanceof CycleTransaction && $record->status === CycleTransactionStatus::InProgress),
        ];
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
                            TextViewField::make('code')->label('Code'),
                            RecordReferenceViewField::make('cycle')
                                ->applicationCode(Cycle::APPLICATION_CODE)
                                ->relation('cycle')
                                ->label('Cycle'),
                            TextViewField::make('subject_type')->label('Subject Type'),
                            TextViewField::make('subject_id')->label('Subject Id'),
                            EnumViewField::make('status')
                                ->label('Status')
                                ->labels(CycleTransactionStatus::options()),
                            DateTimeViewField::make('started_at')->label('Started At'),
                            DateTimeViewField::make('completed_at')->label('Completed At'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('stages')
                ->applicationKey('hr.cycles.transaction.stages')
                ->label('Stages')
                ->table(CycleTransactionLinesTable::class)
                ->relation('lines')
                ->authorization(true),
        ];
    }
}
