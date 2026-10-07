<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SignupController;
use App\Http\Middleware\SignupGate;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// El alta (Fase 2): nombre → cuenta → plan → verificar → listo
Route::prefix('alta')->name('signup')->controller(SignupController::class)->group(function () {
    // El enlace del correo funciona también sin sesión (otro dispositivo): va firmado; fuera de la puerta
    Route::get('/verificar/{user}/{hash}', 'verifyLink')->middleware(['signed', 'throttle:20,1'])->name('.verify.link');

    Route::middleware(SignupGate::class)->group(function () {
        Route::get('/', 'start');
        Route::post('/nombre', 'storeName')->middleware('throttle:30,1')->name('.name');
        Route::post('/cambiar-nombre', 'changeName')->name('.change-name');
        Route::get('/cuenta', 'account')->name('.account');
        Route::post('/cuenta', 'storeAccount')->middleware('throttle:signup')->name('.account.store');

        Route::middleware('auth')->group(function () {
            Route::get('/plan', 'plan')->name('.plan');
            Route::post('/plan', 'storePlan')->name('.plan.store');
            Route::get('/verificar', 'verify')->name('.verify');
            Route::post('/verificar', 'storeVerify')->name('.verify.store');
            Route::post('/verificar/reenviar', 'resend')->name('.verify.resend');
            Route::get('/listo', 'done')->name('.done');
        });
    });
});

// Provisional: los textos legales llegan en la Fase 5
Route::get('/legal/{page}', [PlaceholderController::class, 'legal'])->name('legal');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
