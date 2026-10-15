<?php

namespace App\Services;

use App\Models\Alias;
use App\Models\Mailbox;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Alias de un buzón desde Mi cuenta (D-029). Mismas reglas que los nombres de buzón (NameAvailability), sin
 * nombres cortos con recargo (si no, un alias saltaría el recargo), y hasta `max_aliases` de su plan. Al
 * borrarlo, el nombre se queda en cuarentena como el de un buzón borrado.
 */
class Aliases
{
    /** Límite de los buzones internos (sin plan). */
    public const INTERNAL_MAX = 50;

    public function __construct(private NameAvailability $names) {}

    public function max(Mailbox $mailbox): int
    {
        return $mailbox->isInternal() ? self::INTERNAL_MAX : (int) ($mailbox->plan?->max_aliases ?? 0);
    }

    /** @throws RuntimeException con un mensaje para el usuario */
    public function create(Mailbox $mailbox, string $input): Alias
    {
        if ($mailbox->status !== 'active') {
            throw new RuntimeException('Este buzón no está activo.');
        }
        $max = $this->max($mailbox);
        if ($max === 0) {
            throw new RuntimeException('Los alias van con los planes de pago.');
        }
        if ($mailbox->aliases()->count() >= $max) {
            throw new RuntimeException("Ya tienes los {$max} alias de tu plan. Borra alguno o pasa a un plan con más.");
        }

        $check = $this->names->check($input, $mailbox->domain, withSuggestions: false);
        if ($check->status !== \App\Services\NameCheck::AVAILABLE) {
            throw new RuntimeException($check->message ?? 'Ese nombre no está disponible.');
        }
        if ($check->surchargeCents !== null) {
            throw new RuntimeException('Los nombres cortos no se pueden usar como alias. Prueba uno más largo.');
        }

        return Alias::create([
            'domain_id' => $mailbox->domain_id,
            'source' => $check->localPart.'@'.$mailbox->domain->name,
            'destination' => $mailbox->email,
            'mailbox_id' => $mailbox->id,
            'active' => true,
            'created_at' => now(),
        ]);
    }

    /** Deja de recibir y de poder usarse para enviar; el nombre queda en cuarentena. */
    public function delete(Alias $alias, ?CarbonInterface $releaseAt = null): void
    {
        $alias->update(['active' => false, 'deleted_at' => now(),
            'release_at' => $releaseAt ?? now()->addDays(config('lifecycle.voluntary_release_days'))]);
    }

    /** Al borrar un buzón (por el cliente o por el ciclo de vida), sus alias se van con él. */
    public function retireFor(Mailbox $mailbox, CarbonInterface $releaseAt): void
    {
        foreach ($mailbox->aliases()->get() as $alias) {
            $this->delete($alias, $releaseAt);
        }
    }
}
