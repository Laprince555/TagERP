<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('general', '/general', ModuleWorkspace::class);
ModuleRoute::registerSubModules('general', '/general', SubModuleWorkspace::class);
