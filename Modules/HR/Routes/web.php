<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('hr', '/hr', ModuleWorkspace::class);
ModuleRoute::registerSubModules('hr', '/hr', SubModuleWorkspace::class);
