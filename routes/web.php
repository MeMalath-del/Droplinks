<?php

use App\Http\Controllers\AppExportController;
use App\Http\Controllers\RuntimeController;
use App\Livewire\AppBuilder\AppDetail;
use App\Livewire\AppBuilder\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class);
Route::get('/apps/{app:slug}', AppDetail::class)->name('apps.show');
Route::get('/apps/{app:slug}/export', [AppExportController::class, 'download'])->name('apps.export');
Route::get('/run/{app:slug}/{page?}', [RuntimeController::class, 'show'])
    ->where('page', '.*');
Route::post('/run/{app:slug}/actions/{action}', [RuntimeController::class, 'runAction'])
    ->name('runtime.action');
