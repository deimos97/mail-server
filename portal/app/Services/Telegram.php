<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Mensajes al Telegram del dueño (config/services.php → telegram). Texto en HTML de Telegram: escapa lo que venga de fuera. */
class Telegram
{
    public const MAX_LENGTH = 4096;

    public function enabled(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    public function send(string $html): void
    {
        if (! $this->enabled()) {
            return;
        }

        Http::asForm()->timeout(10)->retry(2, 1000)
            ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                'chat_id' => config('services.telegram.chat_id'),
                'text' => mb_substr($html, 0, self::MAX_LENGTH),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => 'true',
            ])->throw();
    }

    public static function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
