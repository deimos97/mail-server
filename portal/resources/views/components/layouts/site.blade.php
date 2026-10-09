@props(['title' => null, 'description' => null, 'structuredData' => null])
@php
    $title ??= 'Una Grande y Libre · Correo hecho en España';
    $description ??= 'Consigue tu cuenta de correo @unagrandeylibre.es: privada, sin publicidad y con servidores en la Unión Europea.';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @unless (config('app.indexable'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <meta name="theme-color" content="#AA151B">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Vista previa al compartir (WhatsApp, X, Facebook, LinkedIn…) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Una Grande y Libre">
    <meta property="og:locale" content="es_ES">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ asset('img/og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Consigue tu cuenta de correo tunombre@unagrandeylibre.es">
    <meta name="twitter:card" content="summary_large_image">

    @if ($structuredData)
        {!! \App\Support\StructuredData::toScript($structuredData) !!}
    @endif
    @if ($posthogKey = config('services.posthog.key'))
        <script>window.UGL = { posthog: { key: @js($posthogKey) }, experiments: @json((object) (request()->hasSession() ? app(\App\Services\Experiments::class)->assigned() : [])) };</script>
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-papel font-sans text-tinta antialiased">
    {{ $slot }}
    <x-consent-banner />
</body>
</html>
