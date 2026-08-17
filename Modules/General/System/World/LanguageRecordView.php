<?php

namespace Modules\General\System\World;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Language;

/**
 * The authorized record show page for a single Language (package model,
 * gen-wld-lng Application). Mirrors TimezoneRecordView's shape.
 */
class LanguageRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.language';

    public function model(): string
    {
        return Language::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-lng');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Language::query()->whereRaw('1 = 0');
        }

        return Language::query();
    }

    public function applicationCode(): ?string
    {
        return 'gen-wld-lng';
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->code ?: null;
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
                            TextViewField::make('name_native')->label('Native Name'),
                            TextViewField::make('code')->label('Code')->copyable(),
                            TextViewField::make('dir')->label('Direction'),
                        ]),
                ]),
        ];
    }
}
