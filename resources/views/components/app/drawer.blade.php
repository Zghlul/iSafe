@props([
    'title',
    'open' => 'drawerOpen',
    'id' => 'app-drawer',
])

<div x-cloak x-show="{{ $open }}" class="fixed inset-0 z-50" @keydown.escape.window="{{ $open }} = false">
    <button
        type="button"
        class="absolute inset-0 h-full w-full cursor-default"
        style="background: rgba(20, 22, 27, 0.4)"
        @click="{{ $open }} = false"
        aria-label="Tutup panel"
    ></button>

    <section
        id="{{ $id }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        class="absolute inset-y-0 right-0 flex w-full flex-col border-l border-border bg-surface shadow-[0_8px_24px_rgba(20,22,27,0.12)] sm:max-w-[480px]"
    >
        <header class="flex items-center justify-between border-b border-border px-5 py-4">
            <h2 id="{{ $id }}-title" class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
            <button type="button" class="inline-flex size-10 items-center justify-center rounded-md text-text-muted hover:bg-surface-muted hover:text-text" @click="{{ $open }} = false" aria-label="Tutup panel">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </div>
        @isset($footer)
            <footer class="sticky bottom-0 flex justify-end gap-3 border-t border-border bg-surface px-5 py-4">
                {{ $footer }}
            </footer>
        @endisset
    </section>
</div>
