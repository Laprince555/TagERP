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
use Modules\General\Models\World\Currency;

/**
 * The authorized record show page for a single Currency (package model,
 * gen-wld-cur Application). Mirrors CountryRecordView's shape.
 */
class CurrencyRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.currency';

    public function model(): string
    {
        return Currency::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-cur');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Currency::query()->whereRaw('1 = 0');
        }

        return Currency::query();
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
                            TextViewField::make('code')->label('Code')->copyable(),
                            TextViewField::make('symbol')->label('Symbol'),
                            TextViewField::make('symbol_native')->label('Native Symbol'),
                            TextViewField::make('precision')->label('Precision'),
                            TextViewField::make('decimal_mark')->label('Decimal Mark'),
                            TextViewField::make('thousands_separator')->label('Thousands Separator'),
                            RecordReferenceViewField::make('country')
                                ->applicationCode('gen-wld-ctr')
                                ->relation('country')
                                ->label('Country'),
                        ]),
                ]),
        ];
    }
}
