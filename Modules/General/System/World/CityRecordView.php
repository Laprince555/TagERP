<?php

namespace Modules\General\System\World;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Nnjeim\World\Models\City;

/**
 * The authorized record show page for a single City (package model,
 * gen-wld-cty Application). Mirrors CountryRecordView's shape.
 */
class CityRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.city';

    public function model(): string
    {
        return City::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-cty');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return City::query()->whereRaw('1 = 0');
        }

        return City::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->country_code ?: null;
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
                            TextViewField::make('country_code')->label('Country Code')->copyable(),
                            RecordReferenceViewField::make('country')
                                ->applicationCode('gen-wld-ctr')
                                ->relation('country')
                                ->label('Country'),
                            RecordReferenceViewField::make('state')
                                ->applicationCode('gen-wld-sta')
                                ->relation('state')
                                ->label('State'),
                        ]),
                ]),
        ];
    }
}
