<?php

use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

// Prefijo /api (sin sesión ni cookies). Ver bootstrap/app.php.
Route::get('/availability', AvailabilityController::class)
    ->middleware('throttle:availability')
    ->name('api.availability');
