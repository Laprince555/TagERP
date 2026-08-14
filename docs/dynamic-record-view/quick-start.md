# Quick Start

## 1. Define the record view (Core, framework-agnostic)

```php
namespace Modules\General\System;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use Illuminate\Database\Eloquent\Builder;

class SubModuleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.sub-module';

    public function model(): string { return SubModule::class; }

    public function query(): Builder { return SubModule::query(); }

    public function title(mixed $record): string { return (string) $record->name; }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')->default()->contents([
                FieldsContent::make('basic-information')->fields([
                    TextViewField::make('name'),
                    TextViewField::make('code')->copyable(),
                ]),
            ]),
        ];
    }
}
```

## 2. Add a thin Livewire page

```php
namespace Modules\General\Livewire;

use App\Livewire\DynamicRecordView\RecordView;

class SubModuleRecordView extends RecordView
{
    protected function definitionClass(): string
    {
        return \Modules\General\System\SubModuleRecordView::class;
    }
}
```

## 3. Register a route

```php
Route::middleware(['auth'])
    ->get('/general/sub-modules/{recordId}/view', \Modules\General\Livewire\SubModuleRecordView::class)
    ->name('general.sub-modules.view');
```

That's the whole page — header, Basic Information tab, and (if `subApplications()` is defined) an independent Other Data section with embedded Dynamic Tables. See [defining-record-views.md](defining-record-views.md) for the full API and [embedded-tables.md](embedded-tables.md) for wiring in a relation.
