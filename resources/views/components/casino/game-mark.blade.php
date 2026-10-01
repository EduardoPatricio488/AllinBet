@props([
    'slug',
])

@switch($slug)
    @case('coinflip')
        <svg viewBox="0 0 120 120" fill="none"><circle cx="60" cy="60" r="45" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="36" stroke="currentColor" stroke-opacity=".45" stroke-width="1.5"/><path d="M60 27v66M46 43h18a9 9 0 0 1 0 18H49a9 9 0 0 0 0 18h25" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="m60 20 5 8-5 8-5-8 5-8Zm0 56 5 8-5 8-5-8 5-8Z" fill="currentColor"/></svg>
        @break
    @case('dice')
        <svg viewBox="0 0 120 120" fill="none"><path d="m60 15 39 22v46l-39 22-39-22V37l39-22Z" stroke="currentColor" stroke-width="3"/><path d="m21 37 39 23 39-23M60 60v45" stroke="currentColor" stroke-width="2"/><circle cx="43" cy="42" r="4" fill="currentColor"/><circle cx="77" cy="42" r="4" fill="currentColor"/><circle cx="43" cy="77" r="4" fill="currentColor"/><circle cx="77" cy="77" r="4" fill="currentColor"/><circle cx="60" cy="60" r="4" fill="currentColor"/></svg>
        @break
    @case('roulette')
        <svg viewBox="0 0 120 120" fill="none"><circle cx="60" cy="60" r="47" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="37" stroke="currentColor" stroke-opacity=".5" stroke-width="1.5"/><path d="M60 13v24m33-10L77 47m30 13H83M93 93 77 77m-17 30V83m-33 10 16-16M13 60h24m-10-33 16 16" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="16" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="4" fill="currentColor"/></svg>
        @break
    @case('blackjack')
        <svg viewBox="0 0 120 120" fill="none"><rect x="22" y="19" width="48" height="70" rx="7" transform="rotate(-12 22 19)" stroke="currentColor" stroke-width="3"/><rect x="50" y="27" width="48" height="70" rx="7" transform="rotate(9 50 27)" stroke="currentColor" stroke-width="3"/><path d="M40 36c7-8 19-1 15 8-2 5-9 10-9 10s-8-5-10-10c-3-6 0-9 4-8Zm33 11c7-8 19-1 15 8-2 5-9 10-9 10s-8-5-10-10c-3-6 0-9 4-8Z" fill="currentColor"/></svg>
        @break
    @default
        <svg viewBox="0 0 120 120" fill="none"><rect x="15" y="28" width="27" height="64" rx="5" stroke="currentColor" stroke-width="3"/><rect x="47" y="20" width="27" height="80" rx="5" stroke="currentColor" stroke-width="3"/><rect x="79" y="28" width="27" height="64" rx="5" stroke="currentColor" stroke-width="3"/><path d="M23 47h11m-11 18h11m-11 18h11m25-43h11m-11 20h11m-11 20h11m21-15h11m-11 18h11" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><path d="m60 8 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Zm35 79 2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="currentColor"/></svg>
@endswitch
