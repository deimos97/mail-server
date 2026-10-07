<?php

use App\Http\Controllers\MailClientConfigController;
use Illuminate\Support\Facades\Route;

/*
 * Autoconfiguración de apps de correo. Sin el grupo `web`: sin sesión, cookies ni CSRF (las apps no
 * los usan). Se sirven en autoconfig./autodiscover.<dominio> y en el propio dominio (Thunderbird
 * también prueba /.well-known/autoconfig/…). Ver bootstrap/app.php.
 */
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/mail/config-v1.1.xml', [MailClientConfigController::class, 'autoconfig'])->name('autoconfig');
    Route::get('/.well-known/autoconfig/mail/config-v1.1.xml', [MailClientConfigController::class, 'autoconfig']);

    foreach (['/autodiscover/autodiscover.xml', '/Autodiscover/Autodiscover.xml', '/AutoDiscover/AutoDiscover.xml'] as $path) {
        Route::match(['get', 'post'], $path, [MailClientConfigController::class, 'autodiscover']);
    }
});
