<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ringkasan') — {{ config('app.name', 'iSafe') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <div class="min-h-screen p-4 md:flex md:gap-6">
        <div
            x-cloak
            x-show="sidebarOpen"
            class="fixed inset-0 z-40 bg-text/40 md:hidden"
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <aside
            class="sidebar-width fixed inset-y-4 left-4 z-50 flex flex-col rounded-sidebar bg-sidebar-bg px-4 py-5 text-sidebar-text md:sticky md:top-4 md:h-[calc(100vh-2rem)]"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-[calc(100%+1rem)] md:translate-x-0'"
            aria-label="Navigasi utama"
        >
            <a href="{{ route('dashboard') }}" class="mb-8 flex items-center gap-3 px-2 text-on-primary">
                <span class="flex size-9 items-center justify-center rounded-md bg-accent text-text" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 18V6m0 12h16M8 15v-4m4 4V7m4 8v-6" />
                    </svg>
                </span>
                <span>
                    <span class="block text-base font-bold leading-tight">iSafe</span>
                    <span class="block text-xs text-sidebar-text-muted">Penjualan iPhone</span>
                </span>
            </a>

            <nav class="flex-1 space-y-6 overflow-y-auto" aria-label="Menu">
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-sidebar-text-muted">Ruang kerja</p>
                    <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif @class([
                        'flex min-h-10 items-center gap-3 rounded-md px-3 text-sm',
                        'bg-surface font-semibold text-text' => request()->routeIs('dashboard'),
                        'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('dashboard'),
                    ])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="3.5" width="7" height="4" rx="1.5" /><rect x="13.5" y="9.5" width="7" height="11" rx="1.5" /><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                        </svg>
                        Ringkasan
                        @if (request()->routeIs('dashboard'))<span class="ml-auto size-2 rounded-full bg-accent" aria-hidden="true"></span>@endif
                    </a>
                    <a href="{{ route('sales.index') }}" @if (request()->routeIs('sales.*')) aria-current="page" @endif @class(['mt-1 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('sales.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('sales.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 5.5h16v13H4zM8 9h8M8 13h5" />
                        </svg>
                        Transaksi
                    </a>
                    <a href="{{ route('stocks.index') }}" @if (request()->routeIs('stocks.*')) aria-current="page" @endif @class(['mt-1 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('stocks.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('stocks.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5zM4.5 7.8 12 12l7.5-4.2M12 12v8.5M8 5.3l8 4.5" />
                        </svg>
                        Stok
                        @if ($sidebarLowStockCount > 0)<span class="ml-auto rounded-full bg-warning-soft px-2 py-0.5 text-[10px] font-bold text-warning" aria-label="{{ $sidebarLowStockCount }} model menipis">{{ $sidebarLowStockCount }}</span>@endif
                    </a>
                    <a href="{{ route('reports.show', 'monthly') }}" @if (request()->routeIs('reports.*')) aria-current="page" @endif @class(['mt-1 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('reports.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('reports.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                        </svg>
                        Laporan
                    </a>
                </div>

                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-sidebar-text-muted">Kelola</p>
                    <a href="{{ route('models.index') }}" @if (request()->routeIs('models.*')) aria-current="page" @endif @class(['flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('models.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('models.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="7" y="2.75" width="10" height="18.5" rx="2" /><path d="M10 5.75h4M10 18.25h4" />
                        </svg>
                        Model iPhone
                    </a>
                    <a href="{{ route('exports.index') }}" @if (request()->routeIs('exports.*')) aria-current="page" @endif @class(['mt-1 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('exports.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('exports.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3v12m-4-4 4 4 4-4M4 17.5v3h16v-3" />
                        </svg>
                        Export Excel
                    </a>
                    <a href="{{ route('settings.index') }}" @if (request()->routeIs('settings.*')) aria-current="page" @endif @class(['mt-1 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm', 'bg-surface font-semibold text-text' => request()->routeIs('settings.*'), 'text-sidebar-text hover:bg-white/5' => ! request()->routeIs('settings.*')])>
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" /><path d="m19.4 15 .1.1a1.7 1.7 0 1 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 1 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 1 1-2.4-2.4l.1-.1A1.7 1.7 0 0 0 4.2 12h-.2a1.7 1.7 0 1 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 1 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2v-.2a1.7 1.7 0 1 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 1 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 1 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9Z" />
                        </svg>
                        Pengaturan
                    </a>
                </div>
            </nav>

            <div class="mt-5 rounded-lg bg-sidebar-surface p-4">
                <p class="text-xs font-semibold text-on-primary">Target bulanan</p>
                @if ($sidebarMonthlyTarget > 0)
                    <div class="mt-2 flex justify-between gap-2 text-xs text-sidebar-text"><span>Omzet bulan ini</span><span>{{ $sidebarTargetProgress }}%</span></div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-accent" style="width: {{ $sidebarTargetProgress }}%"></div></div>
                    <p class="mt-2 text-xs leading-5 text-sidebar-text-muted">Rp {{ number_format($sidebarMonthlyRevenue, 0, ',', '.') }} / Rp {{ number_format($sidebarMonthlyTarget, 0, ',', '.') }}</p>
                @else
                    <p class="mt-2 text-xs leading-5 text-sidebar-text-muted">Target omzet belum diatur.</p>
                @endif
                <a href="{{ route('settings.index') }}" class="mt-2 inline-block text-xs font-semibold text-sidebar-text hover:text-on-primary">{{ $sidebarMonthlyTarget > 0 ? 'Ubah target' : 'Atur di Pengaturan' }}</a>
            </div>

            <div class="mt-3 rounded-lg border border-white/10 bg-sidebar-surface p-3">
                <div class="flex items-center gap-3">
                    @if ($sidebarStoreLogo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sidebarStoreLogo) }}" alt="" class="size-9 rounded-full object-cover">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-on-primary">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-sidebar-text-muted">Admin</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex min-h-10 w-full items-center gap-2 rounded-md px-2 text-left text-sm text-sidebar-text hover:bg-white/5 hover:text-on-primary">
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1 md:px-2">
            <header class="flex min-h-14 items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex size-10 items-center justify-center rounded-md border border-border-strong bg-surface text-text md:hidden"
                        @click="sidebarOpen = true"
                        aria-label="Buka menu"
                    >
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <p class="truncate text-xs text-text-muted">
                        <span class="hidden sm:inline">Ruang kerja</span>
                        <span class="hidden sm:inline mx-1">/</span>
                        <span class="font-semibold text-text">Ringkasan</span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <div x-data="{ notificationsOpen: false }" class="relative">
                    <button type="button" @click="notificationsOpen = !notificationsOpen" aria-label="Notifikasi transaksi rugi" :aria-expanded="notificationsOpen" class="relative inline-flex size-10 items-center justify-center rounded-md border border-border bg-surface text-text-muted hover:bg-surface-muted">
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4" />
                        </svg>
                    </button>
                    @if ($sidebarLossSales->isNotEmpty())<span class="absolute right-1 top-1 size-2 rounded-full bg-danger" aria-hidden="true"></span>@endif
                    <div x-cloak x-show="notificationsOpen" @click.outside="notificationsOpen = false" class="absolute right-0 top-12 z-30 w-80 rounded-lg border border-border bg-surface p-3 shadow-[0_8px_24px_rgba(20,22,27,0.12)]">
                        <p class="px-2 py-1 text-sm font-semibold">Transaksi rugi bulan ini</p>
                        @forelse ($sidebarLossSales as $loss)
                            <a href="{{ route('sales.index', ['q' => $loss->model]) }}" class="block rounded-md px-2 py-2 text-xs hover:bg-surface-muted">{{ $loss->model }} · {{ $loss->buyer_name }}<span class="block font-semibold text-danger">−Rp {{ number_format(abs($loss->profit), 0, ',', '.') }}</span></a>
                        @empty
                            <p class="px-2 py-3 text-xs text-text-muted">Tidak ada transaksi rugi bulan ini.</p>
                        @endforelse
                    </div>
                    </div>
                    <a href="{{ route('sales.index') }}" class="hidden h-10 items-center gap-2 rounded-md border border-border-strong bg-surface px-3 text-sm font-semibold text-text hover:bg-surface-muted sm:inline-flex">
                        Filter
                    </a>
                    <a href="{{ route('sales.index', ['create' => 1]) }}" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-3 text-sm font-semibold text-on-primary hover:bg-primary-hover">
                        <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        <span class="hidden sm:inline">Tambah transaksi</span>
                        <span class="sm:hidden">Tambah</span>
                    </a>
                </div>
            </header>

            <main class="pb-8 pt-6 md:pt-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
