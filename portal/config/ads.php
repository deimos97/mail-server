<?php

// Conversiones de servidor para las plataformas de anuncios (Fase 5). Apagadas hasta tener las cuentas
// (Fase 6): con las variables vacías no se envía nada. Solo para usuarios que aceptaron las cookies al
// darse de alta y que vienen de un anuncio de esa plataforma (fbclid / gclid…). Ver App\Services\AdConversions.
return [
    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'access_token' => env('META_CAPI_TOKEN'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),   // para probar en el Administrador de eventos
        'api_version' => env('META_API_VERSION', 'v21.0'),
    ],
    'google' => [
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),                 // sin guiones
        'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),     // si se accede a través de una MCC
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'api_version' => env('GOOGLE_ADS_API_VERSION', 'v21'),
        // Acciones de conversión (IDs numéricos de Google Ads → Objetivos → Conversiones)
        'signup_action' => env('GOOGLE_ADS_SIGNUP_ACTION'),
        'purchase_action' => env('GOOGLE_ADS_PURCHASE_ACTION'),
    ],
];
