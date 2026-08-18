<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\CRM\Livewire\Customers\CustomerRecordView;
use Modules\CRM\Livewire\Customers\CustomersIndex;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('crm', '/crm', ModuleWorkspace::class);
ModuleRoute::registerSubModules('crm', '/crm', SubModuleWorkspace::class);

// Customer Management - Customers
Route::middleware(['auth'])
    ->get('/crm/customer-management/customers', CustomersIndex::class)
    ->name('crm.customer-management.customers');

Route::middleware(['auth'])
    ->get('/crm/customer-management/customers/{recordId}/view', CustomerRecordView::class)
    ->name('crm.customer-management.customers.show');
