@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
])

<label class="grid gap-2 text-sm font-semibold text-zinc-100" for="{{ $name }}">
    <span>{{ $label }}</span>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value ?? old($name) }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @if ($errors->has($name)) aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->class(['casino-field']) }}
    >
    @error($name)
        <span id="{{ $name }}-error" class="text-xs font-medium text-rose-300">{{ $message }}</span>
    @enderror
</label>
