<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\SupportTicket;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(fn (SupportTicket $record) => $record->number().' · '.$record->subject)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('email')->label('Responder a')->copyable(),
                        TextEntry::make('user_id')->label('Cuenta')
                            ->state(fn (SupportTicket $record) => $record->user_id ? 'Con sesión (usuario '.$record->user_id.')' : 'Sin sesión'),
                        TextEntry::make('status')->label('Estado')->badge()
                            ->formatStateUsing(fn (string $state) => $state === 'open' ? 'Abierto' : 'Cerrado')
                            ->color(fn (string $state) => $state === 'open' ? 'danger' : 'gray'),
                        TextEntry::make('created_at')->label('Abierto el')->dateTime('d/m/Y H:i', 'Europe/Madrid'),
                    ]),
                ViewEntry::make('thread')->hiddenLabel()->view('filament.support.thread'),
            ]);
    }
}
