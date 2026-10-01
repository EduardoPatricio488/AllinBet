@props([
    'variant' => 'primary',
    'type' => 'button',
])

<button type="{{ $type }}" {{ $attributes->class(['casino-button', 'casino-button--'.$variant]) }}>
    {{ $slot }}
</button>
