<div class="grid gap-8 lg:grid-cols-2">
    <section>
        <h2 class="mb-3 text-base font-semibold">Rondas recentes</h2>
        @if ($rounds->isEmpty())
            <p class="text-sm text-zinc-500">Ainda não há rondas.</p>
        @else
            <div class="divide-y divide-zinc-800">
                @foreach ($rounds as $round)
                    <a href="{{ route('fairness.verify', $round) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:text-emerald-200" wire:navigate>
                        <span><span class="block font-medium">{{ str($round->game->value)->headline() }}</span><span class="text-xs text-zinc-500">{{ $round->created_at->diffForHumans() }} · {{ $round->status->value }}</span></span>
                        <span class="tabular-nums text-zinc-300">{{ $round->payout }} créditos</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
    <section>
        <h2 class="mb-3 text-base font-semibold">Transações recentes</h2>
        @if ($transactions->isEmpty())
            <p class="text-sm text-zinc-500">Ainda não há transações.</p>
        @else
            <div class="divide-y divide-zinc-800">
                @foreach ($transactions as $transaction)
                    <div class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span><span class="block font-medium">{{ str($transaction->type->value)->headline() }}</span><span class="text-xs text-zinc-500">{{ $transaction->created_at->diffForHumans() }}</span></span>
                        <span class="tabular-nums {{ $transaction->amount > 0 ? 'text-emerald-300' : 'text-zinc-300' }}">{{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
