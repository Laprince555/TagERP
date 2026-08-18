<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\Procurement\Livewire\Vendors\Vendors\VendorRecordView;
use Modules\Procurement\Livewire\Vendors\Vendors\VendorsIndex;

ModuleRoute::registerIndex('procurement', '/procurement', ModuleWorkspace::class);
ModuleRoute::registerSubModules('procurement', '/procurement', SubModuleWorkspace::class);

Route::middleware(['auth'])
    ->get('/procurement/vendor-management/vendors', VendorsIndex::class)
    ->name('procurement.vendor-management.vendors');

Route::middleware(['auth'])
    ->get('/procurement/vendor-management/vendors/{recordId}/view', VendorRecordView::class)
    ->name('procurement.vendor-management.vendors.show');
