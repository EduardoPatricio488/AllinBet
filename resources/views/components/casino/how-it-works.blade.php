@props([
    'gameKey',
    'title',
    'description',
    'rules' => [],
    'badge' => 'Como jogar',
])

<div
    x-data="{
        open: false,
        storageKey: 'allinbet:how-it-works:{{ $gameKey }}',
        init() {
            this.open = localStorage.getItem(this.storageKey) !== '1';
        },
        close() {
            this.open = false;
            localStorage.setItem(this.storageKey, '1');
        }
    }"
    x-show="open"
    x-cloak
    x-transition.opacity
    class="casino-how-it-works"
    role="dialog"
    aria-modal="true"
    aria-labelledby="how-it-works-{{ $gameKey }}"
    x-on:keydown.escape.window="close()"
>
    <div class="casino-how-it-works__backdrop" x-on:click="close()"></div>

    <div class="casino-how-it-works__dialog" x-on:click.stop>
        <button type="button" class="casino-how-it-works__close" x-on:click="close()" aria-label="Fechar">
            ×
        </button>

        <div class="casino-how-it-works__header">
            <div>
                <p class="casino-how-it-works__eyebrow">Como jogar</p>
                <h2 id="how-it-works-{{ $gameKey }}">{{ $title }}</h2>
            </div>
            <span class="casino-how-it-works__badge">{{ $badge }}</span>
        </div>

        <p class="casino-how-it-works__description">{{ $description }}</p>

        <div class="casino-how-it-works__rules">
            @foreach ($rules as $index => $rule)
                <div class="casino-how-it-works__rule">
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $rule['title'] }}</strong>
                    <p>{{ $rule['text'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="casino-how-it-works__footer">
            <span>Créditos virtuais · sem valor monetário</span>
            <button type="button" class="casino-how-it-works__start" x-on:click="close()">
                Entendi, começar a jogar
            </button>
        </div>
    </div>
</div>

@once
<style>
    [x-cloak]{display:none!important}
    .casino-how-it-works{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:1rem}
    .casino-how-it-works__backdrop{position:absolute;inset:0;background:rgba(2,5,8,.78);backdrop-filter:blur(9px)}
    .casino-how-it-works__dialog{position:relative;z-index:1;width:min(100%,44rem);max-height:min(90vh,44rem);overflow:auto;padding:1.5rem;border:1px solid rgba(242,193,78,.28);border-radius:1.35rem;background:linear-gradient(145deg,#111820,#0a0e12);box-shadow:0 30px 100px rgba(0,0,0,.72),0 0 60px rgba(242,193,78,.08)}
    .casino-how-it-works__close{position:absolute;top:.7rem;right:.7rem;width:2.25rem;height:2.25rem;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(255,255,255,.05);color:#aeb5bd;font-size:1.4rem;line-height:1;cursor:pointer}
    .casino-how-it-works__close:hover{background:rgba(255,255,255,.1);color:#fff}
    .casino-how-it-works__header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding-right:2.5rem}
    .casino-how-it-works__eyebrow{margin:0 0 .2rem;color:#9ba2aa;font-size:.62rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
    .casino-how-it-works__header h2{margin:0;color:#fff;font-size:1.45rem;font-weight:900}
    .casino-how-it-works__badge{white-space:nowrap;padding:.38rem .65rem;border:1px solid rgba(242,193,78,.2);border-radius:999px;background:rgba(242,193,78,.08);color:#f2c14e;font-size:.6rem;font-weight:900}
    .casino-how-it-works__description{margin:.9rem 0 1rem;color:#aeb5bd;font-size:.78rem;line-height:1.65}
    .casino-how-it-works__rules{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}
    .casino-how-it-works__rule{padding:.85rem;border:1px solid rgba(255,255,255,.06);border-radius:.8rem;background:rgba(0,0,0,.16)}
    .casino-how-it-works__rule>span{display:block;margin-bottom:.4rem;color:#f2c14e;font-size:.58rem;font-weight:950;letter-spacing:.12em}
    .casino-how-it-works__rule strong{display:block;color:#e9edf0;font-size:.73rem}
    .casino-how-it-works__rule p{margin:.35rem 0 0;color:#858d96;font-size:.67rem;line-height:1.5}
    .casino-how-it-works__footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(255,255,255,.07)}
    .casino-how-it-works__footer>span{color:#69737d;font-size:.6rem}
    .casino-how-it-works__start{min-height:2.7rem;padding:.65rem 1rem;border:0;border-radius:.7rem;background:linear-gradient(135deg,#f4ca61,#dca938);color:#15130e;font-size:.72rem;font-weight:950;cursor:pointer;box-shadow:0 8px 25px rgba(220,169,56,.18)}
    .casino-how-it-works__start:hover{filter:brightness(1.06);transform:translateY(-1px)}
    @media(max-width:640px){
        .casino-how-it-works{padding:.7rem}
        .casino-how-it-works__dialog{max-height:92vh;padding:1.1rem}
        .casino-how-it-works__header{flex-direction:column}
        .casino-how-it-works__rules{grid-template-columns:1fr}
        .casino-how-it-works__footer{align-items:stretch;flex-direction:column}
        .casino-how-it-works__start{width:100%}
    }
</style>
@endonce
