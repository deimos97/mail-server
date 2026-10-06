<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\PlaceholderController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// Provisionales: el alta llega en la Fase 2 y los textos legales en la Fase 5
Route::get('/alta', [PlaceholderController::class, 'signup'])->name('signup');
Route::get('/legal/{page}', [PlaceholderController::class, 'legal'])->name('legal');
