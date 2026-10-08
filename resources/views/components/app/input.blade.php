@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
])

@php($inputId = $attributes->get('id', $name))

<div>
    <label for="{{ $inputId }}" class="mb-2 block text-[13px] font-semibold text-text">
        {{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif
    </label>
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @required($required)
        @class([
            'h-11 w-full rounded-md border bg-surface px-3 text-sm text-text placeholder:text-text-muted/70 focus:outline-none focus:ring-2 focus:ring-primary',
            'border-danger focus:border-danger' => $errors->has($name),
            'border-border-strong focus:border-primary' => ! $errors->has($name),
        ])
        @if ($errors->has($name)) aria-describedby="{{ $inputId }}-error" @endif
        {{ $attributes->except(['id'])->merge(['autocomplete' => 'off']) }}
    >
    @error($name)
        <p id="{{ $inputId }}-error" class="mt-2 text-[13px] text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>
