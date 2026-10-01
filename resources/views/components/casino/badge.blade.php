@props([
    'tone' => 'gold',
])

<span {{ $attributes->class(['casino-badge', 'casino-badge--'.$tone]) }}>
    {{ $slot }}
</span>
