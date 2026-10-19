<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport">
        <meta name="theme-color" content="#003599">
        <link rel="manifest" href="/manifest.webmanifest">
        <title inertia>Accommerce.id</title>
        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $initialSeoSchema = data_get($page ?? [], 'props.seo.schema');
        @endphp
        @if (is_array($initialSeoSchema) && $initialSeoSchema !== [])
            <script data-inertia="jsonld" type="application/ld+json">@json($initialSeoSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
        @endif
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="bg-page-background text-on-surface font-body-md text-body-md antialiased selection:bg-primary-fixed selection:text-on-primary-fixed">
        @inertia
    </body>
</html>
