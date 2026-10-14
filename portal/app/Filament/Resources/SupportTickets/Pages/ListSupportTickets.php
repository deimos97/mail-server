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
                ->label(fn () => $support->guestsAllowed() ? 'Sin sesión: permitido' : 'Sin sesión: bloqueado')
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
        ];
    }
}
