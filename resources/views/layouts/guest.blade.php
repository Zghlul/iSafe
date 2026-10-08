<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — {{ config('app.name', 'Bangaldi') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-8">
    <main class="w-full max-w-[420px]">
        <a href="{{ route('login') }}" class="mb-6 flex items-center justify-center gap-3" aria-label="Bangaldi">
            <span class="flex size-11 items-center justify-center rounded-md bg-accent text-text" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 18V6m0 12h16M8 15v-4m4 4V7m4 8v-6" />
                </svg>
            </span>
            <span>
                <span class="block text-xl font-bold leading-tight">Bangaldi</span>
                <span class="block text-xs text-text-muted">Pendataan penjualan iPhone</span>
            </span>
        </a>

        <section class="rounded-lg border border-border bg-surface p-6 sm:p-8">
            {{ $slot }}
        </section>
    </main>
</body>
</html>
