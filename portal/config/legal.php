<?php

// Titular del servicio y datos de los textos legales (resources/views/legal/). La empresa es la de
// tibletech.com; la marca del servicio es el dominio.
return [
    // Mientras sea true, cada página avisa de que es un borrador pendiente de revisión (D-Fase 6)
    'draft' => env('LEGAL_DRAFT', true),
    'updated' => '9 de octubre de 2026',

    'brand' => 'unagrandeylibre.es',
    'company' => 'Tible Technologies, S.L.',
    'nif' => 'B87456554',
    'address' => 'Calle Aviador Zorita, 4, Sótano 1, 28020 Madrid (Madrid)',
    'registry' => 'Inscrita en el Registro Mercantil de Madrid, tomo 34230, folio 24, sección 8, hoja M-615786, inscripción 3',
    'email' => 'soporte@tibletech.com',        // contacto general y ejercicio de derechos
    'phone' => '911 309 855',
    'abuse' => 'abuse@unagrandeylibre.es',     // denuncias de spam o abuso
];
