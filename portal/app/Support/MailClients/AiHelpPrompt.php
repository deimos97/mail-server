<?php

namespace App\Support\MailClients;

use App\Models\Mailbox;

/**
 * Instrucciones para que una IA (ChatGPT, Claude, Gemini…) guíe al usuario a configurar su app de correo.
 * Llevan la dirección y los servidores, **nunca la contraseña**: se le explica a la IA que el usuario la
 * tiene en pantalla y que debe copiarla directamente en la app, no en el chat.
 */
class AiHelpPrompt
{
    public static function make(Mailbox $mailbox, string $client): string
    {
        $c = config('mail_clients');
        $app = $c['clients'][$client]['label'] ?? 'su app de correo';
        $host = $c['host'];
        $guide = route('help.setup.client', $client);

        return <<<TXT
Hola. Necesito que me ayudes a configurar mi cuenta de correo en {$app}. Guíame paso a paso, en español y con lenguaje sencillo: dame un paso cada vez, espera a que te diga que lo he hecho y pregúntame primero qué dispositivo y qué versión de la app tengo para que los pasos coincidan con lo que veo.

Datos de la cuenta (proveedor: {$c['provider_name']}):
- Dirección y nombre de usuario: {$mailbox->email} (siempre la dirección completa)
- Tipo de cuenta: IMAP (no POP3, no Exchange)
- Servidor de entrada (IMAP): {$host}, puerto {$c['imap']['port']}, seguridad SSL/TLS
- Servidor de salida (SMTP): {$host}, puerto {$c['smtp']['port']}, seguridad SSL/TLS, con autenticación (el mismo usuario y contraseña)
- Si el puerto {$c['smtp']['port']} no funciona: puerto {$c['smtp_alt']['port']} con STARTTLS

Sobre la contraseña (importante): tengo en pantalla, en la web de {$c['provider_name']}, una contraseña de 16 letras y números creada solo para este dispositivo. NO me la pidas ni me dejes escribirla en este chat: dime que la copie de esa página y la pegue directamente en la app, en el campo de contraseña (tanto para la entrada como para la salida). Si la app me pide "contraseña de aplicación", es esa misma. No es la contraseña con la que entro en la web.

Si algo falla, ayúdame a revisar en este orden: que el usuario sea la dirección completa, que la contraseña se haya pegado entera y sin espacios, que la seguridad sea SSL/TLS y los puertos sean los de arriba. Si la app intenta configurarse sola, puede hacerlo bien: compruébalo con los datos de arriba. Guía oficial con los pasos: {$guide}
TXT;
    }
}
