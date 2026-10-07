<?php

use App\Models\MailboxReservation;
use Illuminate\Support\Facades\Schedule;

// Tareas programadas (portal-schedule.timer ejecuta schedule:run cada minuto)

// Reservas de nombre caducadas (el alta ya las ignora; esto solo limpia la tabla)
Schedule::call(fn () => MailboxReservation::where('expires_at', '<', now()->subDay())->delete())
    ->hourly()->name('limpiar-reservas')->withoutOverlapping();

// Lista de dominios de email temporales (propaganistas/laravel-disposable-email)
Schedule::command('disposable:update')->weekly()->sundays()->at('04:15');

// Login único: códigos y tokens caducados (los revocados se guardan 30 días por si hay que investigar algo)
Schedule::call(function () {
    \App\Models\OauthCode::where('expires_at', '<', now()->subDay())->delete();
    \App\Models\OauthToken::where('refresh_expires_at', '<', now())->orWhere('revoked_at', '<', now()->subDays(30))->delete();
})->daily()->at('04:30')->name('limpiar-oauth');
