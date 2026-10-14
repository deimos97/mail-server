<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use App\Services\Support;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

/** @property SupportTicket $record */
class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    public function getTitle(): string
    {
        return 'Ticket '.$this->record->number();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Responder')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading(fn () => 'Responder a '.$this->record->email)
                ->modalDescription(fn () => 'Sale de '.config('support.from.address').' con «'.$this->record->emailSubject().'» en el asunto.')
                ->modalSubmitActionLabel('Enviar')
                ->modalWidth('3xl')
                ->schema([
                    Textarea::make('body')->label('Respuesta')->required()->rows(10)->maxLength(10000)
                        ->helperText('Sin saludo final: el correo ya termina con «Un saludo» y tu nombre.'),
                    Toggle::make('close')->label('Cerrar el ticket al enviar')->default(true),
                ])
                ->action(function (array $data) {
                    app(Support::class)->reply($this->record, Auth::user(), $data['body'], (bool) $data['close']);
                    $this->record->refresh();
                    Notification::make()->success()->title('Respuesta enviada')->send();
                }),
            Action::make('close')
                ->label('Cerrar')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->visible(fn () => $this->record->isOpen())
                ->action(function () {
                    app(Support::class)->close($this->record);
                    Notification::make()->success()->title('Ticket cerrado')->send();
                }),
            Action::make('reopen')
                ->label('Reabrir')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => ! $this->record->isOpen())
                ->action(function () {
                    app(Support::class)->reopen($this->record);
                    Notification::make()->success()->title('Ticket reabierto')->send();
                }),
        ];
    }
}
