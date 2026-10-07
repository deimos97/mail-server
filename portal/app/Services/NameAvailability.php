<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\MailboxReservation;
use App\Models\NamePriceTier;
use App\Models\NameRule;
use App\Models\ReservedName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ¿Se puede coger este nombre de buzón? Lo usa la comprobación en vivo de la landing y, en la
 * Fase 2, la creación del buzón (que debe volver a comprobar justo antes del INSERT).
 *
 * Orden: normalizar → reglas de formato → reservados → buzones, alias y reservas de otros → sobrecoste.
 * Pendiente (Fase 4): cuarentena de nombres liberados (D-009).
 *
 * $reservationOwner: token del alta en curso (signup.token); su propia reserva no cuenta como cogida.
 */
class NameAvailability
{
    private const MAX_SUGGESTIONS = 3;

    public function check(string $input, Domain $domain, bool $withSuggestions = true, ?string $reservationOwner = null): NameCheck
    {
        $localPart = $this->normalize($input);
        $rule = NameRule::for($domain->id);

        if ($error = $this->formatError($localPart, $rule)) {
            return new NameCheck(NameCheck::INVALID, $localPart, $domain->name, $error,
                suggestions: $withSuggestions ? $this->suggest($localPart, $domain, $rule, $reservationOwner) : []);
        }

        if ($this->isReserved($localPart)) {
            return new NameCheck(NameCheck::UNAVAILABLE, $localPart, $domain->name, 'Este nombre no está disponible.',
                suggestions: $withSuggestions ? $this->suggest($localPart, $domain, $rule, $reservationOwner) : []);
        }

        if ($this->isTaken($localPart, $domain, $reservationOwner)) {
            return new NameCheck(NameCheck::TAKEN, $localPart, $domain->name, 'Ya está cogido.',
                suggestions: $withSuggestions ? $this->suggest($localPart, $domain, $rule, $reservationOwner) : []);
        }

        $tier = NamePriceTier::forLength(mb_strlen($localPart));

        return new NameCheck(NameCheck::AVAILABLE, $localPart, $domain->name,
            $tier ? 'Nombre corto: solo con planes de pago.' : null,
            surchargeCents: $tier?->price_cents);
    }

    /** Minúsculas, sin espacios y sin "@dominio" si pegan la dirección entera. */
    public function normalize(string $input): string
    {
        $value = mb_strtolower(trim($input));

        if (str_contains($value, '@')) {
            $value = strstr($value, '@', true);
        }

        return $value;
    }

    public function formatError(string $localPart, NameRule $rule): ?string
    {
        $length = mb_strlen($localPart);
        $symbols = $rule->allowed_symbols ?? '';
        $symbolClass = preg_quote($symbols, '/');

        if ($length === 0) {
            return 'Escribe un nombre.';
        }
        if (! preg_match('/^[a-z0-9'.$symbolClass.']+$/', $localPart)) {
            return $symbols === ''
                ? 'Solo letras sin tildes y números.'
                : 'Solo letras sin tildes, números y los símbolos '.implode(' ', mb_str_split($symbols));
        }
        if ($length < $rule->min_length) {
            return "Mínimo {$rule->min_length} caracteres.";
        }
        if ($length > $rule->max_length) {
            return "Máximo {$rule->max_length} caracteres.";
        }
        if ($symbols !== '' && $rule->forbid_edge_symbols && preg_match('/^['.$symbolClass.']|['.$symbolClass.']$/', $localPart)) {
            return 'No puede empezar ni terminar por un símbolo.';
        }
        if ($symbols !== '' && $rule->forbid_consecutive_symbols && preg_match('/['.$symbolClass.']{2}/', $localPart)) {
            return 'No puede tener dos símbolos seguidos.';
        }

        return null;
    }

    private function isReserved(string $localPart): bool
    {
        return ReservedName::where('local_part', $localPart)->exists();
    }

    /**
     * Existe como buzón o como alias (los alias también reciben correo en esa dirección), o alguien
     * lo tiene reservado mientras completa el alta.
     */
    private function isTaken(string $localPart, Domain $domain, ?string $reservationOwner): bool
    {
        $email = $localPart.'@'.$domain->name;
        $db = DB::connection('mailserver');

        return $db->table('mailboxes')->where('email', $email)->exists()
            || $db->table('aliases')->where('source', $email)->exists()
            || MailboxReservation::current()
                ->where('domain_id', $domain->id)
                ->where('local_part', $localPart)
                ->when($reservationOwner, fn ($q) => $q->where('session_id', '!=', $reservationOwner))
                ->exists();
    }

    /**
     * Alternativas disponibles y sin sobrecoste. Si el nombre traía tildes o ñ, la primera
     * propuesta es la versión sin ellas ("peña" → "pena"), tenga o no sobrecoste.
     *
     * @return list<string>
     */
    private function suggest(string $localPart, Domain $domain, NameRule $rule, ?string $reservationOwner = null): array
    {
        $symbols = preg_quote($rule->allowed_symbols ?? '', '/');
        $base = Str::ascii($localPart);
        $base = preg_replace('/[^a-z0-9'.$symbols.']/', '', mb_strtolower($base));
        $base = trim(preg_replace('/(['.($symbols ?: '.').'])\1+/', '$1', $base), $rule->allowed_symbols ?? '');

        if ($base === '') {
            return [];
        }

        $candidates = [$base];
        $separator = str_contains($rule->allowed_symbols ?? '', '.') ? '.' : '';
        foreach (['es', 'mail'] as $suffix) {
            $candidates[] = $base.$separator.$suffix;
        }
        foreach ([1, 2, 3, random_int(10, 99), now()->year] as $number) {
            $candidates[] = $base.$number;
        }

        $suggestions = [];
        foreach (array_unique($candidates) as $candidate) {
            if ($candidate === $localPart) {
                continue;
            }
            $check = $this->check($candidate, $domain, withSuggestions: false, reservationOwner: $reservationOwner);
            // La versión sin tildes de lo que escribió se propone aunque tenga sobrecoste;
            // el resto de propuestas, solo si son gratis.
            $isTransliteration = $candidate === $base;
            if ($check->isAvailable() && ($isTransliteration || ! $check->requiresPaidPlan())) {
                $suggestions[] = $candidate;
            }
            if (count($suggestions) === self::MAX_SUGGESTIONS) {
                break;
            }
        }

        return $suggestions;
    }
}
