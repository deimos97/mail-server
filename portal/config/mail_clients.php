<?php

/*
 * Datos de conexión que se dan a las apps de correo (autoconfig, Autodiscover, perfil de Apple,
 * guías y "Mi cuenta"). Todos los dominios comparten el mismo servidor.
 */
return [

    'host' => env('MAIL_SERVER_HOST', 'mail.unagrandeylibre.es'),

    'imap' => ['port' => 993, 'security' => 'SSL'],          // TLS desde el primer byte
    'smtp' => ['port' => 465, 'security' => 'SSL'],
    'smtp_alt' => ['port' => 587, 'security' => 'STARTTLS'],  // alternativa si el 465 está bloqueado

    'provider_name' => 'Una Grande y Libre',

    // Apps con guía y flujo propios en "Configura un dispositivo"
    'clients' => [
        'iphone' => ['label' => 'iPhone o iPad', 'device' => 'iPhone', 'profile' => true],
        'mac' => ['label' => 'Mac (app Mail)', 'device' => 'Mac', 'profile' => true],
        'android' => ['label' => 'Android (Gmail)', 'device' => 'Android', 'profile' => false],
        'outlook' => ['label' => 'Outlook', 'device' => 'Outlook', 'profile' => false],
        'thunderbird' => ['label' => 'Thunderbird', 'device' => 'Thunderbird', 'profile' => false],
        'otra' => ['label' => 'Otra app', 'device' => 'App de correo', 'profile' => false],
    ],

    // "Que te ayude una IA" (pasos manuales): abre el chat con las instrucciones ya escritas. `url` con {q}
    // si el chat admite la pregunta en el enlace; si no (`null` en `prefill`), se abre el chat y el usuario
    // pega el texto, que copiamos antes al portapapeles. Logos: Simple Icons (CC0), resources/svg/ai/.
    'ai_helpers' => [
        'chatgpt' => ['label' => 'ChatGPT', 'icon' => 'openai', 'color' => '#0d0d0d', 'url' => 'https://chatgpt.com/?q={q}'],
        'claude' => ['label' => 'Claude', 'icon' => 'claude', 'color' => '#D97757', 'url' => 'https://claude.ai/new?q={q}'],
        'gemini' => ['label' => 'Gemini', 'icon' => 'googlegemini', 'color' => '#8E75B2', 'url' => 'https://gemini.google.com/app', 'prefill' => false],
        'deepseek' => ['label' => 'DeepSeek', 'icon' => 'deepseek', 'color' => '#5786FE', 'url' => 'https://chat.deepseek.com/', 'prefill' => false],
        'mistral' => ['label' => 'Le Chat', 'icon' => 'mistralai', 'color' => '#FA520F', 'url' => 'https://chat.mistral.ai/chat?q={q}'],
        'perplexity' => ['label' => 'Perplexity', 'icon' => 'perplexity', 'color' => '#1FB8CD', 'url' => 'https://www.perplexity.ai/search?q={q}'],
    ],

];
