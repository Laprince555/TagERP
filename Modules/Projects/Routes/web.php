<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('projects', '/projects', ModuleWorkspace::class);
ModuleRoute::registerSubModules('projects', '/projects', SubModuleWorkspace::class);
