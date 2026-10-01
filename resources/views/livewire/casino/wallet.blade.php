<div class="casino-wallet-page">
    <section class="casino-wallet-hero">
        <div>
            <p class="casino-eyebrow">CARTEIRA ALLINBET</p>
            <h1>Os teus créditos virtuais</h1>
            <p>Gere o saldo usado nas mesas. Estes créditos não têm valor monetário e não podem ser levantados.</p>
        </div>
        <div class="casino-wallet-hero__balance">
            <span>Saldo disponível</span>
            <strong>{{ number_format($balance, 0, ',', '.') }}</strong>
            <small>CRÉDITOS VIRTUAIS</small>
        </div>
    </section>

    <div class="casino-wallet-grid">
        <section class="casino-card casino-wallet-card">
            <div class="casino-wallet-card__head">
                <div>
                    <p class="casino-eyebrow">CARREGAR SALDO</p>
                    <h2>Adicionar créditos</h2>
                    <p>Escolhe um valor para acrescentar à tua carteira.</p>
                </div>
                <span class="casino-wallet-card__icon" aria-hidden="true">✦</span>
            </div>

            <div class="casino-wallet-amounts">
                @foreach ([100, 500, 1000, 5000] as $option)
                    <button type="button" wire:click="$set('amount', {{ $option }})" class="{{ $amount === $option ? 'is-selected' : '' }}">
                        <strong>{{ number_format($option, 0, ',', '.') }}</strong>
                        <span>créditos</span>
                    </button>
                @endforeach
            </div>

            <button type="button" class="casino-button casino-button--primary casino-wallet-submit" wire:click="addCredits" wire:loading.attr="disabled" wire:target="addCredits">
                <span wire:loading.remove wire:target="addCredits">Adicionar {{ number_format($amount, 0, ',', '.') }} créditos</span>
                <span wire:loading wire:target="addCredits">A adicionar…</span>
            </button>

            @error('amount') <p class="casino-wallet-error" role="alert">{{ $message }}</p> @enderror
            @if ($feedback) <p class="casino-wallet-success" role="status">✓ {{ $feedback }}</p> @endif

            <div class="casino-wallet-notice">
                <strong>Sem dinheiro real</strong>
                <span>Este carregamento é apenas para a experiência virtual do AllinBet. Não existe pagamento, levantamento ou conversão para euros.</span>
            </div>
        </section>

        <section class="casino-card casino-wallet-card">
            <div class="casino-wallet-card__head">
                <div>
                    <p class="casino-eyebrow">MOVIMENTOS</p>
                    <h2>Histórico da carteira</h2>
                    <p>As últimas alterações ao teu saldo.</p>
                </div>
            </div>

            @if ($transactions->isEmpty())
                <div class="casino-wallet-empty">
                    <span aria-hidden="true">◎</span>
                    <strong>Ainda não há movimentos</strong>
                    <p>As alterações ao saldo vão aparecer aqui.</p>
                </div>
            @else
                <div class="casino-wallet-transactions">
                    @foreach ($transactions as $transaction)
                        <div class="casino-wallet-transaction">
                            <span class="casino-wallet-transaction__icon" aria-hidden="true">{{ $transaction->amount > 0 ? '+' : '−' }}</span>
                            <div>
                                <strong>
                                    @switch($transaction->type->value)
                                        @case('credit') Créditos adicionados @break
                                        @case('bonus') Bónus @break
                                        @case('game_bet') Aposta @break
                                        @case('game_payout') Prémio de jogo @break
                                        @default Movimento de saldo
                                    @endswitch
                                </strong>
                                <small>{{ $transaction->created_at->diffForHumans() }} · Saldo {{ number_format($transaction->balance_after, 0, ',', '.') }}</small>
                            </div>
                            <b class="{{ $transaction->amount > 0 ? 'is-positive' : 'is-negative' }}">{{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 0, ',', '.') }}</b>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>