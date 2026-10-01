<div
    class="casino-balance"
    aria-live="polite"
    x-data="{
        displayBalance: {{ $balance }},
        delta: 0,
        showDelta: false,
        deltaTimer: null,
        countTo(nextBalance, amount) {
            const startBalance = Number(this.displayBalance);
            const targetBalance = Number(nextBalance);
            const startTime = performance.now();
            const duration = 650;
            this.delta = Number(amount);
            this.showDelta = true;
            window.clearTimeout(this.deltaTimer);
            this.deltaTimer = window.setTimeout(() => { this.displayBalance = targetBalance; }, duration + 120);
            const step = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                this.displayBalance = Math.round(startBalance + ((targetBalance - startBalance) * progress));
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
            window.setTimeout(() => { this.showDelta = false; }, 1600);
        }
    }"
    x-on:casino-balance-changed.window="countTo($event.detail.balance, $event.detail.delta)"
>
    <span class="casino-balance__label">Créditos virtuais</span>
    <strong class="casino-balance__value" x-text="displayBalance">{{ $balance }}</strong>
    <span
        x-cloak
        x-show="showDelta"
        x-transition.opacity.duration.200ms
        x-bind:class="delta >= 0 ? 'casino-balance__delta casino-balance__delta--up' : 'casino-balance__delta casino-balance__delta--down'"
        x-text="`${delta > 0 ? '+' : ''}${delta}`"
    ></span>
</div>
