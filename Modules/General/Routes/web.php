<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountryRecordView;

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
