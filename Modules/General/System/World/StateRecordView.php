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
use Modules\General\Models\World\State;

/**
 * The authorized record show page for a single State (package model,
 * gen-wld-sta Application). Mirrors CountryRecordView's shape.
 */
class StateRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.state';

    public function model(): string
    {
        return State::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-sta');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return State::query()->whereRaw('1 = 0');
        }

        return State::query();
    }

    public function applicationCode(): ?string
    {
        return 'gen-wld-sta';
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
                        ]),
                ]),
        ];
    }
}
