<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountryRecordView;

ModuleRoute::registerIndex('general', '/general', ModuleWorkspace::class);
ModuleRoute::registerSubModules('general', '/general', SubModuleWorkspace::class);

Route::middleware(['auth'])
    ->get('/general/sub-modules/{recordId}/view', SubModuleRecordView::class)
    ->name('general.sub-modules.view');

Route::middleware(['auth'])
    ->get('/general/world/countries', CountriesIndex::class)
    ->name('general.world.countries');

Route::middleware(['auth'])
    ->get('/general/world/countries/{recordId}/view', CountryRecordView::class)
    ->name('general.world.countries.show');

Route::middleware(['auth'])
    ->get('/docs/{path?}', function (?string $path = null) {
        $path = trim($path ?? '', '/');
        $base = public_path('docs');

        if (empty($path)) {
            $candidates = [$base . '/index.html'];
        } else {
            $candidates = [
                $base . '/' . $path,
                $base . '/' . $path . '.html',
                $base . '/' . $path . '/index.html',
                $base . '/' . $path . '/README.html',
            ];
        }

        $filePath = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        if ($filePath) {
            $mimeType = match (pathinfo($filePath, PATHINFO_EXTENSION)) {
                'css' => 'text/css',
                'js' => 'application/javascript',
                'svg' => 'image/svg+xml',
                'json' => 'application/json',
                'html' => 'text/html',
                default => mime_content_type($filePath) ?: 'text/html',
            };

            return response()->file($filePath, ['Content-Type' => $mimeType]);
        }

        if (file_exists($base . '/index.html')) {
            return response()->file($base . '/index.html');
        }

        abort(404, 'Documentation not found.');
    })
    ->where('path', '.*')
    ->name('general.docs');


