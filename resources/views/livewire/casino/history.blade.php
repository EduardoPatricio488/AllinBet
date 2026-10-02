<div class="grid gap-8 lg:grid-cols-2">
    <section class="lg:col-span-2">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Rondas recentes</h2>
                <p class="mt-1 text-xs text-zinc-500">Aposta, resultado líquido e estado vêm diretamente das rondas guardadas.</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-600">{{ $summary['rounds'] }} rondas nesta vista</span>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-2 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/40 p-3"><span class="block text-[10px] font-bold uppercase tracking-[.14em] text-zinc-600">Apostado</span><strong class="mt-1 block tabular-nums text-sm text-zinc-200">{{ number_format($summary['wagered'], 0, ',', '.') }} CR</strong></div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/40 p-3"><span class="block text-[10px] font-bold uppercase tracking-[.14em] text-zinc-600">Prémios</span><strong class="mt-1 block tabular-nums text-sm text-emerald-300">{{ number_format($summary['payout'], 0, ',', '.') }} CR</strong></div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/40 p-3"><span class="block text-[10px] font-bold uppercase tracking-[.14em] text-zinc-600">Resultado</span><strong class="mt-1 block tabular-nums text-sm {{ ($summary['payout'] - $summary['wagered']) >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ ($summary['payout'] - $summary['wagered']) > 0 ? '+' : '' }}{{ number_format($summary['payout'] - $summary['wagered'], 0, ',', '.') }} CR</strong></div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/40 p-3"><span class="block text-[10px] font-bold uppercase tracking-[.14em] text-zinc-600">Vitórias</span><strong class="mt-1 block tabular-nums text-sm text-zinc-200">{{ number_format($summary['wins']) }}</strong></div>
        </div>

        <div class="mb-3 flex items-end justify-between gap-3">        @if ($rounds->isEmpty())
            <p class="text-sm text-zinc-500">Ainda não há rondas.</p>
        @else
            <div class="divide-y divide-zinc-800">
                @foreach ($rounds as $round)
                    @php
                        $public = $round->publicResult();
                        $effectiveBet = (int) ($public['total_wager'] ?? $round->bet);
                        $net = (int) $round->payout - $effectiveBet;
                        $outcome = $round->status->value !== 'completed'
                            ? 'pending'
                            : ($net > 0 ? 'win' : ($net === 0 ? 'push' : 'loss'));
                        $outcomeLabel = match ($outcome) {
                            'win' => 'Vitória',
                            'push' => 'Empate',
                            'loss' => 'Perda',
                            default => 'Em curso',
                        };
                    @endphp

                    <button type="button"
                            class="flex w-full items-center justify-between gap-4 py-3 text-left transition hover:text-emerald-200"
                            x-data
                            x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $round->id }} })">
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 font-medium">
                                <span>{{ str($round->game->value)->headline() }}</span>
                                <span class="rounded-full border border-zinc-700 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-zinc-500">{{ $outcomeLabel }}</span>
                            </span>
                            <span class="mt-1 block text-xs text-zinc-500">
                                {{ $round->created_at->format('d/m/Y H:i') }}
                                · Aposta {{ number_format($effectiveBet, 0, ',', '.') }} CR
                                · Retorno {{ number_format((int) $round->payout, 0, ',', '.') }} CR
                            </span>
                        </span>
                        <span class="shrink-0 text-right tabular-nums">
                            <strong class="{{ $net > 0 ? 'text-emerald-300' : ($net < 0 ? 'text-rose-300' : 'text-zinc-300') }}">
                                {{ $net > 0 ? '+' : '' }}{{ number_format($net, 0, ',', '.') }}
                            </strong>
                            <small class="mt-0.5 block text-[10px] text-zinc-600">líquido</small>
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Transações recentes</h2>
                <p class="mt-1 text-xs text-zinc-500">Movimentos efetivos da carteira de créditos virtuais.</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-600">20 últimas</span>
        </div>

        @if ($transactions->isEmpty())
            <p class="text-sm text-zinc-500">Ainda não há transações.</p>
        @else
            <div class="divide-y divide-zinc-800">
                @foreach ($transactions as $transaction)
                    <div class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span>
                            <span class="block font-medium">{{ str($transaction->type->value)->headline() }}</span>
                            <span class="text-xs text-zinc-500">{{ $transaction->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="tabular-nums {{ $transaction->amount > 0 ? 'text-emerald-300' : 'text-zinc-300' }}">
                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>