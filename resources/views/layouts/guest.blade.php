<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>Masuk — {{ config('app.name', 'iSafe') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-8">
    <main class="w-full max-w-[420px]">
        <a href="{{ route('login') }}" class="mb-6 flex items-center justify-center gap-3" aria-label="iSafe">
            <img src="{{ asset('images/isafe-logo.png') }}" alt="iSafe" class="h-auto w-32">
            <span>
                <span class="block text-xs text-text-muted">Pendataan penjualan iPhone</span>
            </span>
        </a>

        <section class="rounded-lg border border-border bg-surface p-6 sm:p-8">
            {{ $slot }}
        </section>
    </main>
</body>
</html>
