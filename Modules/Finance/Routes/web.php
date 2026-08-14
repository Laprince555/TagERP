<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('finance', '/finance', ModuleWorkspace::class);
ModuleRoute::registerSubModules('finance', '/finance', SubModuleWorkspace::class);
