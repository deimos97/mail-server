<?php

namespace App\Jobs;

use App\Services\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Por la cola: si Telegram tarda o falla, no frena al cliente que envía el ticket. */
class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public string $html) {}

    public function handle(Telegram $telegram): void
    {
        $telegram->send($this->html);
    }
}
