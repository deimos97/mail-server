<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Autoconfiguración de apps de correo (sin sesión ni cookies; routes/mail-clients.php):
 *   - Thunderbird, K-9/Thunderbird Android, FairEmail, Evolution… → autoconfig (XML de Mozilla)
 *   - Outlook clásico → Autodiscover POX
 * Ambos dicen: IMAP 993 SSL y SMTP 465 SSL (587 STARTTLS de alternativa), usuario = la dirección.
 */
class MailClientConfigController extends Controller
{
    public function autoconfig(): Response
    {
        $c = config('mail_clients');
        $domains = Domain::where('active', true)->orderBy('id')->pluck('name');
        $e = fn (string $v) => htmlspecialchars($v, ENT_XML1);

        $server = fn (string $tag, string $type, array $proto) => <<<XML
            <{$tag} type="{$type}">
              <hostname>{$e($c['host'])}</hostname>
              <port>{$proto['port']}</port>
              <socketType>{$proto['security']}</socketType>
              <authentication>password-cleartext</authentication>
              <username>%EMAILADDRESS%</username>
            </{$tag}>
        XML;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<clientConfig version="1.1">'."\n"
            .'  <emailProvider id="'.$e($domains->first() ?? 'unagrandeylibre.es').'">'."\n"
            .$domains->map(fn ($d) => '    <domain>'.$e($d).'</domain>')->implode("\n")."\n"
            .'    <displayName>'.$e($c['provider_name']).'</displayName>'."\n"
            .'    <displayShortName>'.$e($c['provider_name']).'</displayShortName>'."\n"
            .$server('incomingServer', 'imap', $c['imap'])."\n"
            .$server('outgoingServer', 'smtp', $c['smtp'])."\n"
            .$server('outgoingServer', 'smtp', $c['smtp_alt'])."\n"
            .'    <documentation url="'.$e(route('help.setup')).'"><descr lang="es">Cómo configurar tu correo</descr></documentation>'."\n"
            .'  </emailProvider>'."\n"
            .'</clientConfig>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    /**
     * Outlook manda un XML con la dirección. Se extrae con una expresión regular, sin parsear el XML
     * (así no hay riesgo de XXE con un cuerpo malicioso).
     */
    public function autodiscover(Request $request): Response
    {
        $c = config('mail_clients');
        preg_match('#<EMailAddress>\s*([^<\s]{3,320})\s*</EMailAddress>#i', (string) $request->getContent(), $m);
        $email = isset($m[1]) && filter_var($m[1], FILTER_VALIDATE_EMAIL) ? mb_strtolower($m[1]) : null;
        $e = fn (string $v) => htmlspecialchars($v, ENT_XML1);
        $login = $email ? '<LoginName>'.$e($email).'</LoginName>' : '';

        $protocol = fn (string $type, array $proto, string $extra = '') => "<Protocol><Type>{$type}</Type><Server>{$e($c['host'])}</Server>"
            ."<Port>{$proto['port']}</Port><DomainRequired>off</DomainRequired>{$login}<SPA>off</SPA>"
            .'<SSL>on</SSL><AuthRequired>on</AuthRequired>'.$extra.'</Protocol>';

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            .'<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">'
            .'<Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a">'
            .'<Account><AccountType>email</AccountType><Action>settings</Action>'
            .$protocol('IMAP', $c['imap'])
            .$protocol('SMTP', $c['smtp'], '<UsePOPAuth>on</UsePOPAuth><SMTPLast>off</SMTPLast>')
            .'</Account></Response></Autodiscover>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
