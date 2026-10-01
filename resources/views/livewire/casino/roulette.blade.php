<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}
<div class="casino-game-play relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)]">
    <x-casino.loading-overlay target="prepare,spin" />
    <section class="space-y-5">
        @if (in_array($roundPhase, ['ready', 'completed'], true))
            <form wire:submit="prepare" class="space-y-4">
                <label class="block space-y-1 text-sm">Aposta em créditos
                    <input type="number" min="1" max="{{ config('casino.bet_limits.max') }}" wire:model="bet" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
                </label>
                <label class="block space-y-1 text-sm">Tipo de aposta
                    <select wire:model.live="betType" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
                        <option value="straight">Número</option><option value="color">Cor</option><option value="parity">Paridade</option><option value="range">Baixo / alto</option><option value="dozen">Dúzia</option><option value="column">Coluna</option>
                    </select>
                </label>
                <label class="block space-y-1 text-sm">Seleção
                    @if ($betType === 'straight')
                        <input type="number" min="0" max="36" wire:model="selection" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
                    @elseif (in_array($betType, ['dozen', 'column'], true))
                        <select wire:model="selection" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select>
                    @else
                        <select wire:model="selection" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-white">
                            @if ($betType === 'color')
                                <option value="red">Vermelho</option><option value="black">Preto</option>
                            @elseif ($betType === 'parity')
                                <option value="even">Par</option><option value="odd">Ímpar</option>
                            @else
                                <option value="low">1–18</option><option value="high">19–36</option>
                            @endif
                        </select>
                    @endif
                </label>
                <label class="block space-y-1 text-sm">Seed do cliente
                    <input type="text" maxlength="128" wire:model="clientSeed" class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 font-mono text-sm text-white">
                </label>
                @error('bet') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
                @error('selection') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-rose-400 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-rose-300 disabled:opacity-50">Preparar aposta</button>
            </form>
        @endif
        @if ($roundPhase === 'prepared')
            <div class="space-y-3 border-t border-zinc-800 pt-5">
                <p class="text-xs uppercase text-zinc-500">Hash do servidor antes do resultado</p>
                <p class="break-all font-mono text-xs text-zinc-300">{{ $serverSeedHash }}</p>
                <button type="button" wire:click="spin" wire:loading.attr="disabled" class="rounded-md bg-rose-400 px-4 py-2 text-sm font-semibold text-zinc-950 hover:bg-rose-300 disabled:opacity-50">Girar roleta</button>
            </div>
        @endif
        @error('game') <p role="alert" class="text-sm text-rose-300">{{ $message }}</p> @enderror
        <p wire:loading class="text-sm text-zinc-400">A processar ronda…</p>
    </section>
    <section class="min-h-36 border-l border-zinc-800 ps-6" aria-live="polite">
        @if ($roundResult !== [])
            <p class="text-xs uppercase text-zinc-500">Bola</p>
            <p class="mt-2 text-4xl font-semibold tabular-nums {{ match ($roundResult['color']) { 'red' => 'text-rose-300', 'black' => 'text-white', default => 'text-emerald-300' } }}">{{ $roundResult['outcome'] }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ $roundResult['color'] }} · {{ ($roundResult['won'] ?? false) ? 'Ganhou' : 'Não ganhou' }} · {{ $roundPayout }} créditos</p>
            @if ($roundPhase === 'completed' && $roundId)
                <button type="button" class="mt-4 text-sm text-emerald-300 underline underline-offset-4 hover:text-emerald-200" x-data x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
            @endif
        @elseif ($roundPhase === 'ready')
            <p class="text-sm text-zinc-500">Escolha uma aposta interna ou externa.</p>
        @endif
    </section>
</div>
