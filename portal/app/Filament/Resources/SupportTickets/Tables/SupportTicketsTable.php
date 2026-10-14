<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Models\SupportTicket;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('messages')->with('latestMessage'))
            ->columns([
                TextColumn::make('id')->label('Nº')->formatStateUsing(fn (SupportTicket $record) => $record->number())->sortable(),
                TextColumn::make('subject')->label('Asunto')->searchable()->wrap()
                    ->description(fn (SupportTicket $record) => $record->email.($record->user_id ? '' : ' · sin sesión')),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'open' ? 'Abierto' : 'Cerrado')
                    ->color(fn (string $state) => $state === 'open' ? 'danger' : 'gray'),
                TextColumn::make('waiting')->label('Último mensaje')
                    ->state(fn (SupportTicket $record) => $record->latestMessage?->fromCustomer() ? 'Del cliente' : 'Nuestro')
                    ->badge()->color(fn (string $state) => $state === 'Del cliente' ? 'warning' : 'success'),
                TextColumn::make('messages_count')->label('Mensajes'),
                TextColumn::make('last_message_at')->label('Actividad')->since()->dateTimeTooltip('d/m/Y H:i', 'Europe/Madrid')->sortable(),
                TextColumn::make('created_at')->label('Abierto')->dateTime('d/m/Y H:i', 'Europe/Madrid')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(['open' => 'Abiertos', 'closed' => 'Cerrados'])->default('open'),
            ])
            ->recordActions([ViewAction::make()->label('Abrir')]);
    }
}
