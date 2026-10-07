<?php

namespace App\Services;

use App\Models\AppPassword;
use App\Models\Mailbox;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Contraseñas por dispositivo (D-011). Formato: 16 caracteres de un alfabeto sin ambigüedades
 * (sin 0/o, 1/l/i), ~80 bits. Los 6 primeros son el "selector" con el que Dovecot localiza la fila
 * (ver dovecot-sql-app-passwords.conf.ext); se compara el bcrypt de la contraseña completa.
 * La contraseña en claro se devuelve una sola vez y no se guarda.
 */
class AppPasswords
{
    public const LENGTH = 16;

    public const SELECTOR_LENGTH = 6;

    public const MAX_ACTIVE = 20;

    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** @return array{0: AppPassword, 1: string} el registro y la contraseña en claro */
    public function create(Mailbox $mailbox, string $name): array
    {
        if ($mailbox->appPasswords()->whereNull('revoked_at')->count() >= self::MAX_ACTIVE) {
            throw new RuntimeException('Has llegado al máximo de dispositivos conectados. Revoca alguno antes.');
        }

        // El selector tiene que ser único en el buzón (lo exige la BD); con 31^6 opciones, casi nunca repite
        for ($try = 0; $try < 5; $try++) {
            $plain = $this->generate();
            $selector = substr($plain, 0, self::SELECTOR_LENGTH);
            if (! $mailbox->appPasswords()->where('selector', $selector)->exists()) {
                $record = $mailbox->appPasswords()->create([
                    'name' => mb_substr(trim($name), 0, 64),
                    'selector' => $selector,
                    'password' => '{BLF-CRYPT}'.Hash::driver('bcrypt')->make($plain),
                ]);

                return [$record, $plain];
            }
        }

        throw new RuntimeException('No se ha podido generar la contraseña. Inténtalo de nuevo.');
    }

    private function generate(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
