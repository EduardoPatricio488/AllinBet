<div class="casino-game-play relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)]">
    <x-casino.loading-overlay target="prepare,flip" />
    <section class="space-y-5">
        @if (in_array($roundPhase, ['ready', 'completed'], true))
            <form wire:submit="prepare" class="space-y-4">
                <label class="block space-y-1 text-sm">Aposta em créditos
                    <input type="number" min="1" max="{{ config('casino.bet_limits.max') }}" wire:model="bet" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
                </label>
                @error('bet') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
                <label class="block space-y-1 text-sm">Lado
                    <select wire:model="side" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"><option value="heads">Cara</option><option value="tails">Coroa</option></select>
                </label>
                <label class="block space-y-1 text-sm">Seed do cliente
                    <input type="text" maxlength="128" wire:model="clientSeed" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 font-mono text-sm text-white">
                </label>
                @error('clientSeed') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-emerald-300 disabled:opacity-50">Preparar ronda</button>
            </form>
        @endif
        @if ($roundPhase === 'prepared')
            <div class="space-y-3 border-t border-zinc-800 pt-5">
                <p class="text-xs uppercase text-zinc-500">Hash do servidor antes do resultado</p>
                <p class="break-all font-mono text-xs text-zinc-300">{{ $serverSeedHash }}</p>
                <button type="button" wire:click="flip" wire:loading.attr="disabled" class="rounded-md border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-200 hover:bg-amber-300 hover:text-zinc-950 disabled:opacity-50">Lançar moeda</button>
            </div>
        @endif
        @error('game') <p role="alert" class="text-sm text-rose-300">{{ $message }}</p> @enderror
        <p wire:loading class="text-sm text-zinc-400">A processar ronda…</p>
    </section>
    <section class="min-h-36 border-l border-zinc-800 ps-6" aria-live="polite">
        @if ($roundResult !== [])
            <p class="text-xs uppercase text-zinc-500">Resultado</p>
            <p class="mt-2 text-2xl font-semibold {{ ($roundResult['won'] ?? false) ? 'text-emerald-300' : 'text-rose-300' }}">{{ ($roundResult['outcome'] ?? '') === 'heads' ? 'Cara' : 'Coroa' }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ ($roundResult['won'] ?? false) ? 'Ganhou' : 'Não ganhou' }} · {{ $roundPayout }} créditos</p>
            @if ($roundPhase === 'completed' && $roundId)
                <a href="{{ route('fairness.verify', $roundId) }}" class="mt-4 inline-block text-sm text-emerald-300 underline underline-offset-4" wire:navigate>Verificar ronda</a>
            @endif
        @elseif ($roundPhase === 'ready')
            <p class="text-sm text-zinc-500">Escolha o lado e prepare a ronda.</p>
        @endif
    </section>
</div>
