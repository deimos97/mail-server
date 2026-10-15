<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Services\Support;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        $support = app(Support::class);

        // D-021: si llega spam, se cierra aquí sin desplegar. Con sesión siempre se puede escribir.
        return [
            Action::make('guests')
                ->label(fn () => $support->guestsAllowed() ? 'Formulario sin sesión: permitido' : 'Formulario sin sesión: bloqueado')
                ->icon(fn () => $support->guestsAllowed() ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn () => $support->guestsAllowed() ? 'gray' : 'warning')
                ->requiresConfirmation()
                ->modalHeading(fn () => $support->guestsAllowed() ? '¿Bloquear tickets sin sesión?' : '¿Permitir tickets sin sesión?')
                ->modalDescription(fn () => $support->guestsAllowed()
                    ? 'Solo podrán escribir quienes hayan entrado en su cuenta. Los demás verán un aviso para entrar.'
                    : 'Cualquiera podrá escribir desde /soporte, con Turnstile y límite de envíos por IP.')
                ->action(function () use ($support) {
                    $support->setGuestsAllowed(! $support->guestsAllowed());
                    Notification::make()->success()->title($support->guestsAllowed() ? 'Tickets sin sesión permitidos' : 'Tickets sin sesión bloqueados')->send();
                }),

            // D-028: correos nuevos a soporte@ de quien no es cliente. Bloqueado, se quedan en el buzón (con aviso).
            Action::make('emailGuests')
                ->label(fn () => $support->emailGuestsAllowed() ? 'Email de desconocidos: permitido' : 'Email de desconocidos: bloqueado')
                ->icon(fn () => $support->emailGuestsAllowed() ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                ->color(fn () => $support->emailGuestsAllowed() ? 'gray' : 'warning')
                ->requiresConfirmation()
                ->modalHeading(fn () => $support->emailGuestsAllowed() ? '¿Bloquear tickets por email de desconocidos?' : '¿Permitir tickets por email de desconocidos?')
                ->modalDescription(fn () => $support->emailGuestsAllowed()
                    ? 'Los correos a soporte@ de quien no es cliente ya no abrirán ticket: se quedarán en el buzón y te llegará el aviso. Los clientes siguen abriendo tickets por email.'
                    : 'Cualquier correo nuevo a soporte@ abrirá un ticket (salvo respuestas automáticas, listas, spam y más de 3 por hora del mismo remitente).')
                ->action(function () use ($support) {
                    $support->setEmailGuestsAllowed(! $support->emailGuestsAllowed());
                    Notification::make()->success()->title($support->emailGuestsAllowed() ? 'Tickets por email de desconocidos permitidos' : 'Tickets por email de desconocidos bloqueados')->send();
                }),
        ];
    }
}
