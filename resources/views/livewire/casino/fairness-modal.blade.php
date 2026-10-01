<div
    x-data="{ open: @entangle('open') }"
    x-on:casino-open-fairness.window="$wire.openRound(Number($event.detail.roundId))"
    x-on:keydown.escape.window="if (open) { open = false; $wire.close() }"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="fairness-modal-title"
        x-on:click.self="$wire.close()"
    >
        <div
            x-show="open"
            x-transition
            class="relative max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-2xl border border-emerald-300/20 bg-zinc-950 text-zinc-100 shadow-2xl"
        >
            <header class="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-7">
                <div>
                    <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-emerald-300">Provably Fair · Verificação</p>
                    <h2 id="fairness-modal-title" class="mt-1 text-xl font-bold sm:text-2xl">Como esta ronda aconteceu</h2>
                    @if ($verification)
                        <p class="mt-1 text-sm text-zinc-400">Ronda #{{ $verification['id'] }} · {{ $verification['game_label'] }} · {{ $verification['created_at'] }}</p>
                    @endif
                </div>
                <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-white/10 text-xl text-zinc-400 hover:bg-white/5 hover:text-white" aria-label="Fechar" x-on:click="$wire.close()">×</button>
            </header>

            <div class="max-h-[calc(92vh-80px)] overflow-y-auto px-5 py-5 sm:px-7 sm:py-6">
                @if (!$verification)
                    <div class="py-16 text-center text-sm text-zinc-400">A carregar os dados da ronda…</div>
                @else
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[0.65rem] uppercase tracking-wider text-zinc-500">Aposta</p>
                            <p class="mt-1 text-xl font-bold">{{ number_format($verification['bet'], 0, ',', '.') }}</p>
                            <p class="text-xs text-zinc-500">créditos virtuais</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[0.65rem] uppercase tracking-wider text-zinc-500">Pagamento</p>
                            <p class="mt-1 text-xl font-bold {{ $verification['payout'] > 0 ? 'text-emerald-300' : 'text-zinc-200' }}">{{ $verification['payout'] > 0 ? '+' : '' }}{{ number_format($verification['payout'], 0, ',', '.') }}</p>
                            <p class="text-xs text-zinc-500">créditos creditados</p>
                        </div>
                        <div class="rounded-xl border {{ $verification['commitment_verified'] ? 'border-emerald-300/25 bg-emerald-300/5' : 'border-rose-300/25 bg-rose-300/5' }} p-4">
                            <p class="text-[0.65rem] uppercase tracking-wider text-zinc-500">Compromisso</p>
                            <p class="mt-1 font-bold {{ $verification['commitment_verified'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $verification['commitment_verified'] ? 'Verificado' : 'Não verificado' }}</p>
                            <p class="text-xs text-zinc-500">SHA-256</p>
                        </div>
                    </div>

                    <section class="mt-5 rounded-xl border border-emerald-300/15 bg-emerald-300/[0.035] p-5">
                        <h3 class="font-bold text-white">O que aconteceu, passo a passo</h3>
                        <ol class="mt-4 space-y-3">
                            @foreach ($verification['explanation'] as $index => $line)
                                <li class="flex gap-3 text-sm leading-6 text-zinc-300">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-300/10 text-xs font-bold text-emerald-300">{{ $index + 1 }}</span>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <section class="mt-5 rounded-xl border border-white/10 bg-white/[0.025] p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="font-bold">Como funciona a prova criptográfica</h3>
                                <p class="mt-1 text-sm text-zinc-400">{{ $verification['mechanism']['detail'] }}</p>
                            </div>
                            <span class="rounded-full border {{ $verification['commitment_verified'] ? 'border-emerald-300/25 bg-emerald-300/10 text-emerald-300' : 'border-rose-300/25 bg-rose-300/10 text-rose-300' }} px-3 py-1 text-xs font-bold">{{ $verification['commitment_verified'] ? 'HASH CONFERE' : 'HASH NÃO CONFERE' }}</span>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <div class="rounded-lg border border-white/10 bg-black/20 p-3">
                                <p class="text-[0.62rem] uppercase tracking-wider text-zinc-500">1 · Seed do servidor revelado</p>
                                <p class="mt-2 break-all font-mono text-xs leading-5 text-zinc-300">{{ $verification['server_seed'] ?? 'Ainda não revelado' }}</p>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-black/20 p-3">
                                <p class="text-[0.62rem] uppercase tracking-wider text-zinc-500">2 · SHA-256 do seed</p>
                                <p class="mt-2 break-all font-mono text-xs leading-5 text-zinc-300">{{ $verification['server_seed_hash'] }}</p>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-black/20 p-3">
                                <p class="text-[0.62rem] uppercase tracking-wider text-zinc-500">3 · Seed do cliente</p>
                                <p class="mt-2 break-all font-mono text-xs leading-5 text-zinc-300">{{ $verification['client_seed'] }}</p>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-black/20 p-3">
                                <p class="text-[0.62rem] uppercase tracking-wider text-zinc-500">4 · Nonce</p>
                                <p class="mt-2 font-mono text-sm text-zinc-300">{{ $verification['nonce'] }}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-cyan-300/15 bg-cyan-300/[0.035] p-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-cyan-200">A regra de derivação</p>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">Para os jogos determinísticos, o AllinBet calcula <code class="rounded bg-black/30 px-1.5 py-0.5 text-cyan-200">HMAC-SHA-256(seed do servidor, seed do cliente : nonce : contador)</code>. O primeiro bloco hexadecimal é convertido num número e reduzido para o intervalo necessário pelo jogo. O método rejeita valores fora do intervalo uniforme para evitar enviesamento da distribuição.</p>
                        </div>
                    </section>

                    <section class="mt-5 rounded-xl border border-white/10 bg-white/[0.025] p-5">
                        <h3 class="font-bold">O que esta verificação prova</h3>
                        <div class="mt-3 space-y-2 text-sm leading-6 text-zinc-400">
                            <p>• <strong class="text-zinc-200">Prova de compromisso:</strong> depois de veres o hash antes da ronda, podes calcular SHA-256 do seed revelado e confirmar que é exatamente o mesmo hash.</p>
                            <p>• <strong class="text-zinc-200">Reprodução:</strong> nos jogos que usam o mecanismo determinístico, quem tiver o seed revelado, o seed do cliente e o nonce consegue reproduzir o valor base usado pelo jogo.</p>
                            <p>• <strong class="text-zinc-200">Não prova:</strong> a verificação não transforma uma ronda em aleatória por si só, nem garante que todas as regras do jogo estão corretas. Ela verifica especificamente o compromisso e, nos jogos determinísticos, a derivação criptográfica.</p>
                        </div>
                    </section>

                    @if ($verification['game'] === 'blackjack')
                        <section class="mt-5 rounded-xl border border-amber-300/20 bg-amber-300/[0.04] p-5">
                            <h3 class="font-bold text-amber-200">Limitação atual do Blackjack</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">{{ $verification['mechanism']['detail'] }}</p>
                        </section>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
