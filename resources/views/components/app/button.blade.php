@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'border border-primary bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'border border-border-strong bg-surface text-text hover:bg-surface-muted',
        'quiet' => 'border border-transparent bg-transparent text-text-muted hover:bg-surface-muted hover:text-text',
    ];
    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs',
        'md' => 'min-h-10 px-4 text-sm',
        'lg' => 'min-h-11 px-4 text-sm',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-md font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]) }}
>
    {{ $slot }}
</button>
