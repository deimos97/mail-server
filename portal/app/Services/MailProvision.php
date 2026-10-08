<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/** Llama al script privilegiado `mail-provision` (ver server/bin/mail-provision). */
class MailProvision
{
    public function enabled(): bool
    {
        return filled(config('mail_provision.command'));
    }

    /** Firma un perfil de Apple con el certificado de la web (iOS lo muestra como "Verificado"). */
    public function signProfile(string $xml): string
    {
        return $this->run(['sign-profile'], $xml);
    }

    /** Cierra las conexiones abiertas de un buzón (p. ej. tras revocar un dispositivo). */
    public function kick(string $email): void
    {
        $this->run(['kick', $email]);
    }

    private function run(array $arguments, ?string $input = null): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('mail-provision no está configurado');
        }

        $result = Process::timeout(config('mail_provision.timeout'))
            ->input($input)
            ->run([...preg_split('/\s+/', trim(config('mail_provision.command'))), ...$arguments]);

        if ($result->failed()) {
            throw new RuntimeException('mail-provision '.$arguments[0].' falló: '.trim($result->errorOutput()));
        }

        return $result->output();
    }
}
