<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * The authorized record show page for a single Entity (hr-org-ent
 * Application). Mirrors CompanyRecordView's shape.
 */
class EntityRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.organization-structure.entity';

    public function model(): string
    {
        return Entity::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Entity::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Entity::query()->whereRaw('1 = 0');
        }

        return Entity::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->legal_form ?: null;
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
                            RecordReferenceViewField::make('company')
                                ->applicationCode('gen-wld-com')
                                ->relation('company')
                                ->label('Company'),
                            RecordReferenceViewField::make('parent')
                                ->applicationCode('hr-org-ent')
                                ->relation('parent')
                                ->label('Parent Entity'),
                            BooleanViewField::make('is_holding')->label('Holding'),
                            NumberViewField::make('depth')->label('Depth in Tree'),
                        ]),
                    FieldsContent::make('administrative')
                        ->heading('Administrative')
                        ->fields([
                            RecordReferenceViewField::make('currency')
                                ->applicationCode('gen-wld-cur')
                                ->relation('currency')
                                ->label('Currency'),
                            TextViewField::make('legal_form')->label('Legal Form'),
                            NumberViewField::make('fiscal_year_start_month')->label('Fiscal Year Start Month'),
                            NumberViewField::make('fiscal_year_start_day')->label('Fiscal Year Start Day'),
                            NumberViewField::make('ownership_percentage')->label('Ownership %'),
                            TextViewField::make('tax_authority')->label('Tax Authority'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
