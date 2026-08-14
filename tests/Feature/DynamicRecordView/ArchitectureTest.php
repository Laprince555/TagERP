<?php

arch('Core layer never depends on Modules, application models, Livewire, or Blade')
    ->expect('App\Support\DynamicRecordView\Core')
    ->not->toUse(['Modules', 'App\Models', 'Livewire', 'Illuminate\View', 'Illuminate\Support\Facades\Blade'])
    ->ignoring('Calebporzio\Sushi\Sushi');

arch('Resolution layer never depends on Modules or Livewire')
    ->expect('App\Support\DynamicRecordView\Resolution')
    ->not->toUse(['Modules', 'Livewire'])
    ->ignoring('Calebporzio\Sushi\Sushi');
