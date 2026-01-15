<?php

use App\Http\Controllers\RuntimeController;
use App\Livewire\AppBuilder\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class);
Route::get('/run/{app:slug}/{page?}', [RuntimeController::class, 'show'])
    ->where('page', '.*');
