<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\World\Cities\CitiesIndex;
use Modules\General\Livewire\World\Cities\CityRecordView;
use Modules\General\Livewire\World\Companies\CompaniesIndex;
use Modules\General\Livewire\World\Companies\CompanyRecordView;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountryRecordView;
use Modules\General\Livewire\World\Currencies\CurrenciesIndex;
use Modules\General\Livewire\World\Currencies\CurrencyRecordView;
use Modules\General\Livewire\World\People\PeopleIndex;
use Modules\General\Livewire\World\People\PersonRecordView;
use Modules\General\Livewire\World\States\StateRecordView;
use Modules\General\Livewire\World\States\StatesIndex;
use Modules\General\Livewire\World\Timezones\TimezoneRecordView;
use Modules\General\Livewire\World\Timezones\TimezonesIndex;

ModuleRoute::registerIndex('general', '/general', ModuleWorkspace::class);
ModuleRoute::registerSubModules('general', '/general', SubModuleWorkspace::class);

Route::middleware(['auth'])
    ->get('/general/sub-modules/{recordId}/view', SubModuleRecordView::class)
    ->name('general.sub-modules.view');

Route::middleware(['auth'])
    ->get('/general/world/countries', CountriesIndex::class)
    ->name('general.world.countries');

Route::middleware(['auth'])
    ->get('/general/world/countries/{recordId}/view', CountryRecordView::class)
    ->name('general.world.countries.show');

Route::middleware(['auth'])
    ->get('/general/world/states', StatesIndex::class)
    ->name('general.world.states');

Route::middleware(['auth'])
    ->get('/general/world/states/{recordId}/view', StateRecordView::class)
    ->name('general.world.states.show');

Route::middleware(['auth'])
    ->get('/general/world/cities', CitiesIndex::class)
    ->name('general.world.cities');

Route::middleware(['auth'])
    ->get('/general/world/cities/{recordId}/view', CityRecordView::class)
    ->name('general.world.cities.show');

Route::middleware(['auth'])
    ->get('/general/world/currencies', CurrenciesIndex::class)
    ->name('general.world.currencies');

Route::middleware(['auth'])
    ->get('/general/world/currencies/{recordId}/view', CurrencyRecordView::class)
    ->name('general.world.currencies.show');

Route::middleware(['auth'])
    ->get('/general/world/timezones', TimezonesIndex::class)
    ->name('general.world.timezones');

Route::middleware(['auth'])
    ->get('/general/world/timezones/{recordId}/view', TimezoneRecordView::class)
    ->name('general.world.timezones.show');

Route::middleware(['auth'])
    ->get('/general/world/companies', CompaniesIndex::class)
    ->name('general.world.companies');

Route::middleware(['auth'])
    ->get('/general/world/companies/{recordId}/view', CompanyRecordView::class)
    ->name('general.world.companies.show');

Route::middleware(['auth'])
    ->get('/general/world/people', PeopleIndex::class)
    ->name('general.world.people');

Route::middleware(['auth'])
    ->get('/general/world/people/{recordId}/view', PersonRecordView::class)
    ->name('general.world.people.show');

Route::middleware(['auth'])
    ->get('/docs/{path?}', function (?string $path = null) {
        $path = trim($path ?? '', '/');
        $base = public_path('docs');

        if (empty($path)) {
            $candidates = [$base.'/index.html'];
        } else {
            $candidates = [
                $base.'/'.$path,
                $base.'/'.$path.'.html',
                $base.'/'.$path.'/index.html',
                $base.'/'.$path.'/README.html',
            ];
        }

        $filePath = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        if ($filePath) {
            $mimeType = match (pathinfo($filePath, PATHINFO_EXTENSION)) {
                'css' => 'text/css',
                'js' => 'application/javascript',
                'svg' => 'image/svg+xml',
                'json' => 'application/json',
                'html' => 'text/html',
                default => mime_content_type($filePath) ?: 'text/html',
            };

            return response()->file($filePath, ['Content-Type' => $mimeType]);
        }

        if (file_exists($base.'/index.html')) {
            return response()->file($base.'/index.html');
        }

        abort(404, 'Documentation not found.');
    })
    ->where('path', '.*')
    ->name('general.docs');
