<?php

namespace App\Support;

/**
 * Se queda con lo que el cliente ha escrito en una respuesta por email: quita la cita del correo
 * anterior ("El … escribió:", "On … wrote:", "-----Mensaje original-----", el bloque "De:/Enviado:"
 * de Outlook, las líneas con ">") y la firma tras "-- ". Si al limpiar no queda nada, devuelve el texto entero.
 */
class EmailReplyText
{
    public static function fromHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|head)\b.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<br\s*/?>|</(p|div|li|tr|h[1-6]|blockquote)>#i', "\n", $html) ?? $html;

        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $original = trim($text);

        $cuts = [
            '/^[ \t>]*(El|On|Le|Am)\s.{0,300}?(escribió|wrote|a écrit|schrieb)\s*:[ \t]*$/imsu',   // Gmail, Apple Mail (puede ocupar 2 líneas)
            '/^[ \t]*-{2,}\s*(Mensaje original|Original Message|Forwarded message|Mensaje reenviado)\s*-{2,}/imu',
            '/^[ \t]*_{10,}[ \t]*\n+[ \t]*(De|From)\s*:/imu',                                           // Outlook web
            '/^[ \t]*(De|From)\s*:.+\n[ \t]*(Enviado|Sent|Fecha|Date)\s*:/imu',                          // Outlook escritorio
            '/^-- ?$/mu',                                                                               // firma
        ];
        foreach ($cuts as $pattern) {
            if (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
                $text = substr($text, 0, $match[0][1]);
            }
        }

        // Lo citado que quede al final (líneas que empiezan por ">")
        $lines = explode("\n", rtrim($text));
        while ($lines && (str_starts_with(ltrim(end($lines)), '>') || trim(end($lines)) === '')) {
            array_pop($lines);
        }
        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? '');

        return $text !== '' ? $text : $original;
    }
}
