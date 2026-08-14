<?php

use App\Support\ModuleRoute;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('crm', '/crm', ModuleWorkspace::class);
ModuleRoute::registerSubModules('crm', '/crm', SubModuleWorkspace::class);
