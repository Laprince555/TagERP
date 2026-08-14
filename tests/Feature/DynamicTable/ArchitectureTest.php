<?php

arch('Core layer never depends on Modules or application Eloquent models')
    ->expect('App\Support\DynamicTable\Core')
    ->not->toUse(['Modules', 'App\Models'])
    ->ignoring('Calebporzio\Sushi\Sushi');

arch('Query layer never depends on Modules, application models, or Livewire')
    ->expect('App\Support\DynamicTable\Query')
    ->not->toUse(['Modules', 'App\Models', 'Livewire'])
    ->ignoring('Calebporzio\Sushi\Sushi');

arch('no forbidden third party table package is referenced anywhere in the engine')
    ->expect('App\Support\DynamicTable')
    ->not->toUse([
        'PowerGrid',
        'Filament\\Tables',
        'Rappasoft\\LaravelLivewireTables',
    ])
    ->ignoring('Calebporzio\Sushi\Sushi');

arch('the eloquent preference store implements the preference store contract')
    ->expect('App\Support\DynamicTable\PreferenceStores\EloquentTablePreferenceStore')
    ->toImplement('App\Support\DynamicTable\Core\TablePreferenceStore');

arch('the eloquent saved view store implements the saved view store contract')
    ->expect('App\Support\DynamicTable\PreferenceStores\EloquentSavedTableViewStore')
    ->toImplement('App\Support\DynamicTable\Core\SavedTableViewStore');
