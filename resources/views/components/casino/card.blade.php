@props([
    'glow' => false,
])

<section {{ $attributes->class(['casino-card', 'casino-card--glow' => $glow]) }}>
    {{ $slot }}
</section>
