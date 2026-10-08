@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'neutral' => 'bg-surface-muted text-text-muted',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-1 text-xs font-bold leading-4', $tones[$tone] ?? $tones['neutral']]) }}>
    {{ $slot }}
</span>
