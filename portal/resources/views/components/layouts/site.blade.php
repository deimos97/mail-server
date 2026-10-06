@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Una Grande y Libre · Correo hecho en España' }}</title>
    <meta name="description" content="{{ $description ?? 'Consigue tu cuenta de correo @unagrandeylibre.es: privada, sin publicidad y con servidores en la Unión Europea.' }}">
    @unless (config('app.indexable'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <meta name="theme-color" content="#AA151B">
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($posthogKey = config('services.posthog.key'))
        <script>window.UGL = { posthog: { key: @js($posthogKey) } };</script>
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-papel font-sans text-tinta antialiased">
    {{ $slot }}
    <x-consent-banner />
</body>
</html>
