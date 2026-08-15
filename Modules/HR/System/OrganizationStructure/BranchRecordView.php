<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Branch;

/**
 * The authorized record show page for a single Branch (hr-org-brn
 * Application). Mirrors EntityRecordView's shape.
 */
class BranchRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.organization-structure.branch';

    public function model(): string
    {
        return Branch::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Branch::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Branch::query()->whereRaw('1 = 0');
        }

        return Branch::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->is_main ? __('Main Branch') : null;
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
                            RecordReferenceViewField::make('entity')
                                ->applicationCode('hr-org-ent')
                                ->relation('entity')
                                ->label('Entity'),
                            BooleanViewField::make('is_main')->label('Main Branch'),
                            RecordReferenceViewField::make('city')
                                ->applicationCode('gen-wld-cty')
                                ->relation('city')
                                ->label('City'),
                            TextViewField::make('address')->label('Address'),
                            TextViewField::make('phone')->label('Phone'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
