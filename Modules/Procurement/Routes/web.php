<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('procurement', '/procurement', ModuleWorkspace::class);
ModuleRoute::registerSubModules('procurement', '/procurement', SubModuleWorkspace::class);
