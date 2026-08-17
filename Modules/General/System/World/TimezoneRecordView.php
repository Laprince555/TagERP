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
use Modules\General\Models\World\Timezone;

/**
 * The authorized record show page for a single Timezone (package model,
 * gen-wld-tzn Application). Mirrors CountryRecordView's shape.
 */
class TimezoneRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.timezone';

    public function model(): string
    {
        return Timezone::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-tzn');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Timezone::query()->whereRaw('1 = 0');
        }

        return Timezone::query();
    }

    public function applicationCode(): ?string
    {
        return 'gen-wld-tzn';
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
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
                            TextViewField::make('name')->label('Name')->copyable(),
                            RecordReferenceViewField::make('country')
                                ->applicationCode('gen-wld-ctr')
                                ->relation('country')
                                ->label('Country'),
                        ]),
                ]),
        ];
    }
}
