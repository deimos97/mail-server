<?php

return [

    /*
     * Si el alta está abierta al público. Hasta terminar la Fase 2, cerrada: /alta muestra
     * "abre muy pronto" salvo a quien entre con /alta?acceso=<preview_token> (pruebas del dueño).
     */
    'open' => (bool) env('SIGNUP_OPEN', false),

    'preview_token' => env('SIGNUP_PREVIEW_TOKEN'),

];
