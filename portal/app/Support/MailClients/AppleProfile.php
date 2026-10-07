<?php

namespace App\Support\MailClients;

use App\Models\AppPassword;
use App\Models\Mailbox;
use Illuminate\Support\Str;

/**
 * Perfil de configuración de Apple (.mobileconfig) para la app Mail de iPhone, iPad y Mac.
 * Lleva dentro la contraseña del dispositivo: se genera al conectarlo y se descarga una sola vez.
 * Sin firmar de momento (iOS lo muestra como "No verificado"); ver roadmap.
 */
class AppleProfile
{
    public const CONTENT_TYPE = 'application/x-apple-aspen-config';

    public static function make(Mailbox $mailbox, AppPassword $device, string $password): string
    {
        $c = config('mail_clients');
        $reverse = implode('.', array_reverse(explode('.', $mailbox->domain->name)));
        $id = "{$reverse}.mail.{$mailbox->id}.{$device->id}";

        $account = [
            'PayloadType' => 'com.apple.mail.managed',
            'PayloadVersion' => 1,
            'PayloadIdentifier' => "{$id}.account",
            'PayloadUUID' => Str::uuid()->toString(),
            'PayloadDisplayName' => $mailbox->email,
            'EmailAccountDescription' => $mailbox->email,
            'EmailAccountName' => '',
            'EmailAccountType' => 'EmailTypeIMAP',
            'EmailAddress' => $mailbox->email,
            'IncomingMailServerAuthentication' => 'EmailAuthPassword',
            'IncomingMailServerHostName' => $c['host'],
            'IncomingMailServerPortNumber' => $c['imap']['port'],
            'IncomingMailServerUseSSL' => true,
            'IncomingMailServerUsername' => $mailbox->email,
            'IncomingPassword' => $password,
            'OutgoingMailServerAuthentication' => 'EmailAuthPassword',
            'OutgoingMailServerHostName' => $c['host'],
            'OutgoingMailServerPortNumber' => $c['smtp']['port'],
            'OutgoingMailServerUseSSL' => true,
            'OutgoingMailServerUsername' => $mailbox->email,
            'OutgoingPasswordSameAsIncomingPassword' => true,
            'SMIMEEnabled' => false,
            'PreventMove' => false,
            'PreventAppSheet' => false,
        ];

        $profile = [
            'PayloadType' => 'Configuration',
            'PayloadVersion' => 1,
            'PayloadIdentifier' => $id,
            'PayloadUUID' => Str::uuid()->toString(),
            'PayloadDisplayName' => "Correo {$mailbox->email}",
            'PayloadDescription' => "Configura {$mailbox->email} en la app Mail.",
            'PayloadOrganization' => $c['provider_name'],
            'PayloadRemovalDisallowed' => false,
            'PayloadContent' => [$account],
        ];

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">'."\n"
            .'<plist version="1.0">'.self::value($profile).'</plist>'."\n";
    }

    private static function value(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '<true/>' : '<false/>',
            is_int($value) => "<integer>{$value}</integer>",
            is_array($value) && array_is_list($value) => '<array>'.implode('', array_map(self::value(...), $value)).'</array>',
            is_array($value) => '<dict>'.implode('', array_map(
                fn ($k, $v) => '<key>'.htmlspecialchars($k, ENT_XML1).'</key>'.self::value($v), array_keys($value), $value)).'</dict>',
            default => '<string>'.htmlspecialchars((string) $value, ENT_XML1).'</string>',
        };
    }
}
