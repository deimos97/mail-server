<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks de Stripe: sin el grupo `web` (ni sesión ni CSRF). La firma la comprueba el controlador.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');
