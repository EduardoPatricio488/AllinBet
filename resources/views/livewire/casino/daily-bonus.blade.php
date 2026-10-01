<div class="casino-daily-bonus" x-on:daily-bonus-claimed.window="$el.classList.add('casino-daily-bonus--claimed')">
    <div class="casino-daily-bonus__icon" aria-hidden="true">
        <svg viewBox="0 0 48 48" fill="none"><path d="M15 9h18v5a9 9 0 0 1-18 0V9Z" stroke="currentColor" stroke-width="2.5"/><path d="M15 12H8v4a8 8 0 0 0 8 8m17-12h7v4a8 8 0 0 1-8 8M24 23v11m-8 6h16m-13 0v-6h10v6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="m24 2 1.5 4.5L30 8l-4.5 1.5L24 14l-1.5-4.5L18 8l4.5-1.5L24 2Z" fill="currentColor"/></svg>
    </div>
    <div class="casino-daily-bonus__body">
        <p class="casino-eyebrow">PRESENTE DIÁRIO</p>
        <h3>Bónus de créditos</h3>
        <p>Um pequeno reforço virtual para a próxima mesa.</p>
        <strong class="casino-daily-bonus__amount">{{ $bonusCredits }} <small>CRÉDITOS</small></strong>
        <p class="casino-daily-bonus__reward-label">RECOMPENSA DE HOJE</p>
        <p>{{ $bonusCredits }} créditos virtuais para a sua próxima sessão.</p>
        @if ($feedback)
            <p class="casino-daily-bonus__feedback" role="status">{{ $feedback }}</p>
        @endif
        @error('bonus') <p class="casino-daily-bonus__error" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="casino-daily-bonus__action">
        @if ($claimedToday)
            <button class="casino-button casino-button--quiet" type="button" disabled>Resgatado hoje</button>
        @else
            <button class="casino-button casino-button--primary" type="button" wire:click="claim" wire:loading.attr="disabled" wire:target="claim">
                <span wire:loading.remove wire:target="claim">Resgatar bónus</span>
                <span wire:loading wire:target="claim">A adicionar…</span>
            </button>
        @endif
        <span class="casino-daily-bonus__timer-label">Próxima renovação</span>
        <time
            class="casino-daily-bonus__timer"
            aria-label="Tempo até à próxima renovação diária"
            x-data="{
                remaining: 0,
                timer: null,
                init() { this.update(); this.timer = window.setInterval(() => this.update(), 1000); },
                destroy() { window.clearInterval(this.timer); },
                update() { const now = new Date(); const next = new Date(now); next.setHours(24, 0, 0, 0); this.remaining = Math.max(0, Math.floor((next - now) / 1000)); },
                pad(value) { return String(value).padStart(2, '0'); },
                get label() { const hours = Math.floor(this.remaining / 3600); const minutes = Math.floor((this.remaining % 3600) / 60); const seconds = this.remaining % 60; return `${this.pad(hours)}:${this.pad(minutes)}:${this.pad(seconds)}`; }
            }"
            x-text="label"
        ></time>
    </div>
</div>
