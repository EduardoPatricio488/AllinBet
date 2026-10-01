@props([
    'height' => 'h-5',
    'width' => 'w-full',
])

<span {{ $attributes->class(['casino-skeleton block', $height, $width]) }} aria-hidden="true"></span>
