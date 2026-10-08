<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DeviceSetupController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\RecoveryEmailController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SignupController;
use App\Http\Middleware\SignupGate;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// El alta (Fase 2): nombre → cuenta → plan → verificar → listo
Route::prefix('alta')->name('signup')->controller(SignupController::class)->group(function () {
    // El enlace del correo funciona también sin sesión (otro dispositivo): va firmado; fuera de la puerta
    Route::get('/verificar/{user}/{hash}', 'verifyLink')->middleware(['signed', 'throttle:20,1'])->name('.verify.link');

    // La puerta solo cierra la creación de cuentas; quien ya tiene cuenta puede terminar (plan, verificar…)
    Route::middleware(SignupGate::class)->group(function () {
        Route::get('/', 'start');
        Route::post('/nombre', 'storeName')->middleware('throttle:30,1')->name('.name');
        Route::post('/cambiar-nombre', 'changeName')->name('.change-name');
        Route::get('/cuenta', 'account')->name('.account');
        Route::post('/cuenta', 'storeAccount')->middleware('throttle:signup')->name('.account.store');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/plan', 'plan')->name('.plan');
        Route::post('/plan', 'storePlan')->name('.plan.store');
        Route::get('/verificar', 'verify')->name('.verify');
        Route::post('/verificar', 'storeVerify')->name('.verify.store');
        Route::post('/verificar/reenviar', 'resend')->name('.verify.resend');
        Route::get('/listo', 'done')->name('.done');
        Route::get('/pago/{checkout}', 'paymentReturn')->name('.payment.return');
        Route::get('/pago/{checkout}/cancelado', 'paymentCancel')->name('.payment.cancel');
    });
});

// Clientes: entrar, recuperar contraseña y su cuenta
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/recuperar', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/recuperar', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/recuperar/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/recuperar/nueva', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/cuenta', [AccountController::class, 'show'])->name('account');
    Route::post('/cuenta/dispositivos/{device}/revocar', [AccountController::class, 'revokeDevice'])->name('account.devices.revoke');
    Route::post('/cuenta/dispositivos/{device}/nombre', [AccountController::class, 'renameDevice'])->name('account.devices.rename');
    Route::post('/cuenta/copia/{mailbox}', [AccountController::class, 'requestExport'])->middleware('throttle:5,60')->name('account.export');
    Route::get('/cuenta/copia/{export}/descargar', [AccountController::class, 'downloadExport'])->name('account.export.download');
    Route::get('/cuenta/email', [RecoveryEmailController::class, 'edit'])->name('account.email');
    Route::post('/cuenta/email', [RecoveryEmailController::class, 'store'])->middleware('throttle:5,60')->name('account.email.store');
    Route::post('/cuenta/email/confirmar', [RecoveryEmailController::class, 'confirm'])->name('account.email.confirm');
    Route::post('/cuenta/email/cancelar', [RecoveryEmailController::class, 'cancel'])->name('account.email.cancel');
    Route::get('/cuenta/borrar', [AccountDeletionController::class, 'show'])->name('account.delete');
    Route::post('/cuenta/borrar', [AccountDeletionController::class, 'destroy'])->middleware('throttle:5,60')->name('account.delete.destroy');
    Route::get('/cuenta/webmail/{mailbox}', [AccountController::class, 'openWebmail'])->name('account.webmail');
    Route::get('/cuenta/configurar/{mailbox}', [DeviceSetupController::class, 'choose'])->name('setup');
    Route::post('/cuenta/configurar/{mailbox}', [DeviceSetupController::class, 'store'])->middleware('throttle:10,1')->name('setup.store');
    Route::get('/cuenta/perfil/{token}', [DeviceSetupController::class, 'profile'])->name('setup.profile');

    // Login único con el webmail (D-010): Roundcube manda aquí al usuario
    Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
    Route::post('/oauth/authorize', [OAuthController::class, 'choose'])->name('oauth.choose');
});

// Guías para configurar el correo en cada app
Route::get('/ayuda/configurar', [HelpController::class, 'index'])->name('help.setup');
Route::get('/ayuda/configurar/{client}', [HelpController::class, 'show'])->name('help.setup.client');

// Provisional: los textos legales llegan en la Fase 5
Route::get('/legal/{page}', [PlaceholderController::class, 'legal'])->name('legal');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
