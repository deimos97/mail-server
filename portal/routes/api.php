<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

// Prefijo /api (sin sesión ni cookies). Ver bootstrap/app.php.
Route::get('/availability', AvailabilityController::class)
    ->middleware('throttle:availability')
    ->name('api.availability');

// Proveedor OAuth2 del login único (servidor a servidor: Roundcube y Dovecot)
Route::post('/oauth/token', [OAuthController::class, 'token'])->middleware('throttle:60,1')->name('oauth.token');
Route::get('/oauth/userinfo', [OAuthController::class, 'userinfo'])->middleware('throttle:120,1')->name('oauth.userinfo');
Route::post('/oauth/introspect', [OAuthController::class, 'introspect'])->name('oauth.introspect');
