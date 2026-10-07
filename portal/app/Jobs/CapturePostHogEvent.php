<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/** Envía un evento a PostHog desde el servidor (por la cola: el alta no espera a PostHog). */
class CapturePostHogEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public array $payload) {}

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        Http::timeout(10)->acceptJson()
            ->post(rtrim(config('services.posthog.host'), '/').'/i/v0/e/', [
                'api_key' => config('services.posthog.key'),
                ...$this->payload,
            ])
            ->throw();
    }
}
