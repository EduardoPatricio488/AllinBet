<div>
    {{-- Do your work, then step back. --}}
<div class="casino-game-play relative space-y-6">
    <x-casino.loading-overlay target="prepare,deal,hit,stand,double" />
    @if (in_array($roundPhase, ['ready', 'completed'], true))
        <form wire:submit="prepare" class="max-w-sm space-y-4">
            <label class="block space-y-1 text-sm">Aposta em créditos
                <input type="number" min="1" max="{{ config('casino.bet_limits.max') }}" wire:model="bet" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
            </label>
            @error('bet') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
            <label class="block space-y-1 text-sm">Seed do cliente
                <input type="text" maxlength="128" wire:model="clientSeed" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 font-mono text-sm text-white">
            </label>
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-sky-300 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-sky-200 disabled:opacity-50">Preparar mesa</button>
        </form>
    @endif
    @if ($roundPhase === 'prepared')
        <div class="max-w-xl space-y-3 border-t border-zinc-800 pt-5">
            <p class="text-xs uppercase text-zinc-500">Hash do servidor antes do resultado</p>
            <p class="break-all font-mono text-xs text-zinc-300">{{ $serverSeedHash }}</p>
            <button type="button" wire:click="deal" wire:loading.attr="disabled" class="rounded-md bg-sky-300 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-sky-200 disabled:opacity-50">Dar cartas</button>
        </div>
    @endif
    @if ($roundResult !== [])
        <section class="grid gap-8 border-t border-zinc-800 pt-5 md:grid-cols-2" aria-live="polite">
            <div>
                <p class="text-xs uppercase text-zinc-500">Dealer · {{ $roundResult['dealer_visible_total'] ?? '' }}</p>
                <div class="mt-3 flex gap-2">
                    @foreach ($roundResult['dealer_hand'] ?? [] as $card)
                        <span class="flex h-20 w-14 items-center justify-center rounded-md border border-zinc-600 bg-zinc-100 text-xl font-semibold text-zinc-950">{{ ($card['hidden'] ?? false) ? '?' : match ($card['rank']) { 1 => 'A', 11 => 'J', 12 => 'Q', 13 => 'K', default => $card['rank'] } }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">A sua mão · {{ $roundResult['player_total'] ?? '' }}</p>
                <div class="mt-3 flex gap-2">
                    @foreach ($roundResult['player_hand'] ?? [] as $card)
                        <span class="flex h-20 w-14 items-center justify-center rounded-md border border-zinc-600 bg-zinc-100 text-xl font-semibold text-zinc-950">{{ match ($card['rank']) { 1 => 'A', 11 => 'J', 12 => 'Q', 13 => 'K', default => $card['rank'] } }}</span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @if ($roundPhase === 'in_progress')
        <div class="flex flex-wrap gap-2">
            @if (in_array('hit', $roundResult['available_actions'] ?? [], true))
                <button type="button" wire:click="hit" wire:loading.attr="disabled" class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950">Hit</button>
                <button type="button" wire:click="stand" wire:loading.attr="disabled" class="rounded-md border border-zinc-600 px-4 py-2 text-sm font-semibold text-white">Stand</button>
            @endif
            @if (in_array('double', $roundResult['available_actions'] ?? [], true))
                <button type="button" wire:click="double" wire:loading.attr="disabled" class="rounded-md border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-200">Double</button>
            @endif
        </div>
    @elseif ($roundPhase === 'completed')
        <p class="text-lg font-semibold {{ ($roundResult['outcome'] ?? '') === 'player' ? 'text-emerald-300' : 'text-zinc-200' }}">{{ match ($roundResult['outcome'] ?? '') { 'player' => 'Ganhou', 'push' => 'Empate', default => 'Dealer venceu' } }} · {{ $roundPayout }} créditos</p>
        <a href="{{ route('fairness.verify', $roundId) }}" class="inline-block text-sm text-emerald-300 underline underline-offset-4" wire:navigate>Verificar ronda</a>
    @endif
    @error('game') <p role="alert" class="text-sm text-rose-300">{{ $message }}</p> @enderror
    <p wire:loading class="text-sm text-zinc-400">A processar mão…</p>
</div>
