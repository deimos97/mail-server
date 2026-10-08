<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Mapas para Rspamd (límites de envío por plan): un buzón por línea. Solo desde el propio servidor. */
class RspamdMapController extends Controller
{
    public function __invoke(Request $request, string $tier): Response
    {
        abort_unless(in_array($request->ip(), config('mail_limits.allowed_ips'), true), 403);
        $tiers = config('mail_limits.paid_tiers');
        abort_unless($tier === 'paid' || in_array($tier, $tiers, true), 404);

        $emails = Mailbox::whereIn('status', ['active', 'suspended'])
            ->whereIn('tier', $tier === 'paid' ? $tiers : [$tier])
            ->orderBy('email')->pluck('email');

        return response($emails->map(fn ($e) => mb_strtolower($e))->implode("\n")."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-store',
        ]);
    }
}
