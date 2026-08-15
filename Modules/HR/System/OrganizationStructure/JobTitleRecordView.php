<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitleGradesTable;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * The authorized record show page for a single JobTitle (hr-org-jbt
 * Application). Mirrors EntityRecordView's shape.
 */
class JobTitleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.organization-structure.job-title';

    public function model(): string
    {
        return JobTitle::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JobTitle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JobTitle::query()->whereRaw('1 = 0');
        }

        return JobTitle::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return null;
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
                            RecordReferenceViewField::make('department')
                                ->applicationCode('hr-org-dep')
                                ->relation('department')
                                ->label('Department'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('grades')
                ->applicationKey('hr.organization-structure.job-title.grades')
                ->label('Grades')
                ->table(JobTitleGradesTable::class)
                ->relation('jobGrades')
                ->authorization(true),
        ];
    }
}
