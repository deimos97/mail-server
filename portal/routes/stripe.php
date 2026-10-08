<?php

use App\Http\Controllers\RspamdMapController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks de Stripe: sin el grupo `web` (ni sesión ni CSRF). La firma la comprueba el controlador.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Mapas internos para Rspamd (límites de envío por plan). Sin sesión; solo IPs del propio servidor.
Route::get('/internal/rspamd/{tier}.map', RspamdMapController::class)
    ->where('tier', '[a-z]+')->middleware('throttle:120,1')->name('internal.rspamd-map');
