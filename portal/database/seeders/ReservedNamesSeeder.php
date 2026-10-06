<?php

namespace Database\Seeders;

use App\Models\ReservedName;
use Illuminate\Database\Seeder;

/**
 * Nombres reservados de partida. Idempotente: solo añade los que falten; no toca lo que se
 * haya cambiado desde el admin. Se puede ejecutar en producción.
 */
class ReservedNamesSeeder extends Seeder
{
    /** Buzones que todo dominio de correo necesita o que suplantan al servicio. */
    private const SISTEMA = [
        'postmaster', 'abuse', 'hostmaster', 'webmaster', 'root', 'admin', 'administrator',
        'administrador', 'mailer-daemon', 'noreply', 'no-reply', 'nobody', 'security', 'seguridad',
        'support', 'soporte', 'help', 'ayuda', 'info', 'contacto', 'contact', 'billing', 'facturacion',
        'legal', 'privacy', 'privacidad', 'dpo', 'sales', 'ventas', 'staff', 'team', 'equipo',
        'system', 'sistema', 'mail', 'email', 'correo', 'smtp', 'imap', 'pop', 'pop3', 'www', 'ftp',
        'autoconfig', 'autodiscover', 'dmarc', 'dkim', 'spf', 'test', 'status', 'estado', 'api',
    ];

    private const MARCA = [
        'unagrandeylibre', 'una-grande-y-libre', 'una.grande.y.libre', 'unagrande', 'grandeylibre',
    ];

    public function run(): void
    {
        foreach (self::SISTEMA as $name) {
            ReservedName::firstOrCreate(['local_part' => $name], ['reason' => 'sistema']);
        }

        foreach (self::MARCA as $name) {
            ReservedName::firstOrCreate(['local_part' => $name], ['reason' => 'marca']);
        }
    }
}
