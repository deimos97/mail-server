<?php

use App\Models\MailboxReservation;
use Illuminate\Support\Facades\Schedule;

// Tareas programadas (portal-schedule.timer ejecuta schedule:run cada minuto)

// Reservas de nombre caducadas (el alta ya las ignora; esto solo limpia la tabla)
Schedule::call(fn () => MailboxReservation::where('expires_at', '<', now()->subDay())->delete())
    ->hourly()->name('limpiar-reservas')->withoutOverlapping();

// Lista de dominios de email temporales (propaganistas/laravel-disposable-email)
Schedule::command('disposable:update')->weekly()->sundays()->at('04:15');
