@props([
    'slug',
])

@php
    $game = \App\Support\CasinoCatalog::find($slug);
    $catalog = $casinoGames ?? \App\Support\CasinoCatalog::games();
@endphp

@if ($game)
    <div class="casino-game-chrome">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-casino-gold-bright" wire:navigate>← Casino</a>
        <div class="casino-game-chrome__head">
            <div>
                <p class="casino-eyebrow">JOGO {{ $game['code'] }} · {{ $game['tag'] }}</p>
                <h1 class="mt-1 text-3xl">{{ $game['name'] }}</h1>
                <p class="mt-2 max-w-xl text-sm text-zinc-400">{{ $game['blurb'] }}</p>
            </div>
            <nav class="casino-game-chrome__switch" aria-label="Outros jogos">
                @foreach ($catalog as $item)
                    <a href="{{ route($item['route']) }}" class="{{ $item['slug'] === $slug ? 'is-current' : '' }}" wire:navigate>{{ $item['name'] }}</a>
                @endforeach
            </nav>
        </div>
        <div class="casino-felt">
            {{ $slot }}
        </div>
    </div>
@else
    {{ $slot }}
@endif
