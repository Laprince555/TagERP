<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\Security\Permissions\PermissionRecordView;
use Modules\General\Livewire\Security\Permissions\PermissionsIndex;
use Modules\General\Livewire\Security\Roles\RoleRecordView;
use Modules\General\Livewire\Security\Roles\RolesIndex;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\System\Backups\BackupManager;
use Modules\General\Livewire\System\Backups\BackupRecordView;
use Modules\General\Livewire\System\Users\UserRecordView;
use Modules\General\Livewire\System\Users\UsersIndex;
use Modules\General\Livewire\World\Cities\CitiesIndex;
use Modules\General\Livewire\World\Cities\CityRecordView;
use Modules\General\Livewire\World\Companies\CompaniesIndex;
use Modules\General\Livewire\World\Companies\CompanyRecordView;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountryRecordView;
use Modules\General\Livewire\World\Currencies\CurrenciesIndex;
use Modules\General\Livewire\World\Currencies\CurrencyRecordView;
use Modules\General\Livewire\World\Languages\LanguageRecordView;
use Modules\General\Livewire\World\Languages\LanguagesIndex;
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
    ->get('/general/world/languages', LanguagesIndex::class)
    ->name('general.world.languages');

Route::middleware(['auth'])
    ->get('/general/world/languages/{recordId}/view', LanguageRecordView::class)
    ->name('general.world.languages.show');

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
    ->get('/general/security/roles', RolesIndex::class)
    ->name('general.security.roles');

Route::middleware(['auth'])
    ->get('/general/security/roles/{recordId}/view', RoleRecordView::class)
    ->name('general.security.roles.show');

Route::middleware(['auth'])
    ->get('/general/security/permissions', PermissionsIndex::class)
    ->name('general.security.permissions');

Route::middleware(['auth'])
    ->get('/general/security/permissions/{recordId}/view', PermissionRecordView::class)
    ->name('general.security.permissions.show');

Route::middleware(['auth'])
    ->get('/general/system/users', UsersIndex::class)
    ->name('general.system.users');

Route::middleware(['auth'])
    ->get('/general/system/users/{recordId}/view', UserRecordView::class)
    ->name('general.system.users.show');

Route::middleware(['auth'])
    ->get('/general/system/backups', BackupManager::class)
    ->name('general.system.backups');

Route::middleware(['auth'])
    ->get('/general/system/backups/{recordId}/view', BackupRecordView::class)
    ->name('general.system.backups.show');

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

        // realpath() collapses `..` before the containment test, so
        // `/docs/../.env` — and every encoded or symlinked variant of it —
        // resolves outside $root and is simply not a candidate. Testing the
        // resolved path rather than the raw segment is what makes this
        // exhaustive instead of a blocklist.
        $root = realpath($base);

        $filePath = null;
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);

            if ($resolved === false || $root === false || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (is_file($resolved) && ! str_starts_with(basename($resolved), '.')) {
                $filePath = $resolved;
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
