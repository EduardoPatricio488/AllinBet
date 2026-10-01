<div class="sports-page">
    <style>
        .sports-page{
            --sp-gold:#f5c451;
            --sp-gold-hi:#ffe8aa;
            --sp-green:#49e0a3;
            --sp-red:#ff767d;
            --sp-cyan:#51d8ff;
            --sp-panel:#0b1017;
            --sp-panel-2:#111822;
            color:#e9eef2;
        }

        .sports-top{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:1rem;
            align-items:stretch;
            margin-bottom:1rem;
        }
        .sports-hero{
            position:relative;
            overflow:hidden;
            min-height:9.7rem;
            padding:1.35rem 1.4rem;
            border:1px solid rgba(245,196,81,.14);
            border-radius:1.15rem;
            background:
                radial-gradient(circle at 85% 20%,rgba(245,196,81,.14),transparent 33%),
                radial-gradient(circle at 72% 100%,rgba(81,216,255,.07),transparent 35%),
                linear-gradient(145deg,rgba(18,24,33,.98),rgba(7,11,17,.98));
            box-shadow:0 20px 55px rgba(0,0,0,.2);
        }
        .sports-hero:after{
            content:"";
            position:absolute;
            inset:auto -8% -48% 40%;
            height:10rem;
            border-radius:50%;
            border:1px solid rgba(245,196,81,.08);
            transform:rotate(-10deg);
            pointer-events:none;
        }
        .sports-kicker{
            margin:0;
            color:#778696;
            font-size:.62rem;
            font-weight:950;
            letter-spacing:.18em;
            text-transform:uppercase;
        }
        .sports-title{
            position:relative;
            z-index:1;
            margin:.28rem 0 0;
            color:#f7fafb;
            font-size:2.05rem;
            line-height:1;
            font-weight:1000;
            letter-spacing:-.04em;
        }
        .sports-sub{
            position:relative;
            z-index:1;
            max-width:44rem;
            margin:.55rem 0 0;
            color:#8996a5;
            font-size:.73rem;
            line-height:1.5;
        }
        .sports-hero__chips{
            position:relative;
            z-index:1;
            display:flex;
            flex-wrap:wrap;
            gap:.4rem;
            margin-top:.8rem;
        }
        .sports-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.35rem .52rem;
            border:1px solid rgba(255,255,255,.065);
            border-radius:999px;
            background:rgba(255,255,255,.025);
            color:#8b98a6;
            font-size:.53rem;
            font-weight:900;
        }
        .sports-chip strong{color:#dce4ea}

        .sports-head-actions{
            display:grid;
            grid-template-rows:1fr 1fr;
            gap:.7rem;
            min-width:14rem;
        }
        .sports-balance{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.8rem;
            padding:.8rem .95rem;
            border:1px solid rgba(245,196,81,.13);
            border-radius:1rem;
            background:linear-gradient(145deg,rgba(245,196,81,.07),rgba(255,255,255,.022));
        }
        .sports-balance span{
            display:block;
            color:#72808f;
            font-size:.5rem;
            font-weight:900;
            letter-spacing:.1em;
            text-transform:uppercase;
        }
        .sports-balance strong{
            display:block;
            margin-top:.14rem;
            color:#fff;
            font-size:1.05rem;
            font-weight:1000;
        }
        .sports-balance--history{
            width:100%;
            border-color:rgba(81,216,255,.14);
            background:linear-gradient(145deg,rgba(81,216,255,.07),rgba(255,255,255,.02));
            text-align:left;
            cursor:pointer;
            transition:transform .16s,border-color .16s,background .16s,box-shadow .16s;
        }
        .sports-balance--history:hover{
            transform:translateY(-2px);
            border-color:rgba(81,216,255,.35);
            background:linear-gradient(145deg,rgba(81,216,255,.11),rgba(255,255,255,.035));
            box-shadow:0 12px 30px rgba(0,0,0,.16);
        }
        .sports-balance--history strong{font-size:.8rem}
        .sports-balance--history small{
            display:block;
            margin-top:.16rem;
            color:#6f8090;
            font-size:.51rem;
            font-weight:800;
        }

        .sports-toolbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.8rem;
            margin-bottom:.8rem;
            padding:.6rem .7rem;
            border:1px solid rgba(255,255,255,.055);
            border-radius:.9rem;
            background:rgba(10,15,22,.68);
        }
        .sports-live-indicator{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            color:#95a2af;
            font-size:.56rem;
            font-weight:900;
        }
        .sports-live-indicator i{
            display:block;
            width:.4rem;
            height:.4rem;
            border-radius:50%;
            background:#707d89;
            box-shadow:0 0 0 4px rgba(112,125,137,.08);
        }
        .sports-toolbar__meta{
            color:#657484;
            font-size:.55rem;
        }

        .sports-layout{
            display:grid;
            grid-template-columns:minmax(0,1fr) 22rem;
            gap:1rem;
            align-items:start;
        }
        .sports-main{min-width:0}
        .sports-tabs{
            display:flex;
            gap:.45rem;
            overflow-x:auto;
            padding:.05rem .05rem .55rem;
            scrollbar-width:none;
        }
        .sports-tabs::-webkit-scrollbar{display:none}
        .sports-tab{
            flex:0 0 auto;
            display:inline-flex;
            align-items:center;
            gap:.38rem;
            min-height:2.15rem;
            padding:.56rem .78rem;
            border:1px solid rgba(255,255,255,.075);
            border-radius:.72rem;
            background:rgba(255,255,255,.025);
            color:#8492a0;
            font-size:.61rem;
            font-weight:950;
            cursor:pointer;
            transition:.15s;
        }
        .sports-tab:hover{
            border-color:rgba(245,196,81,.22);
            color:#c7d0d8;
            transform:translateY(-1px);
        }
        .sports-tab.is-active{
            border-color:rgba(245,196,81,.4);
            background:linear-gradient(180deg,rgba(245,196,81,.11),rgba(245,196,81,.045));
            color:var(--sp-gold-hi);
            box-shadow:0 0 0 1px rgba(245,196,81,.05);
        }
        .sports-filter-note{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.7rem;
            margin:.05rem 0 .75rem;
            color:#637181;
            font-size:.54rem;
        }
        .sports-filter-note strong{color:#8e9baa}

        .sports-list{display:grid;gap:.72rem}
        .sports-match{
            overflow:hidden;
            border:1px solid rgba(255,255,255,.075);
            border-radius:1.02rem;
            background:linear-gradient(145deg,rgba(13,18,25,.98),rgba(7,11,16,.98));
            box-shadow:0 15px 36px rgba(0,0,0,.13);
            transition:border-color .16s,transform .16s,box-shadow .16s;
        }
        .sports-match:hover{
            border-color:rgba(255,255,255,.12);
            transform:translateY(-1px);
            box-shadow:0 20px 42px rgba(0,0,0,.18);
        }
        .sports-match.is-featured{
            border-color:rgba(245,196,81,.18);
            box-shadow:0 20px 50px rgba(245,196,81,.045);
        }
        .sports-match__top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
            padding:.74rem .86rem;
            border-bottom:1px solid rgba(255,255,255,.05);
        }
        .sports-match__league{
            display:flex;
            align-items:center;
            gap:.5rem;
            min-width:0;
        }
        .sports-league-icon{
            display:grid;
            place-items:center;
            width:1.7rem;
            height:1.7rem;
            flex:0 0 auto;
            border:1px solid rgba(255,255,255,.07);
            border-radius:.52rem;
            background:rgba(255,255,255,.025);
            font-size:.72rem;
        }
        .sports-meta{
            display:block;
            margin-bottom:.1rem;
            color:#6f7f8f;
            font-size:.51rem;
            font-weight:800;
        }
        .sports-league{
            display:block;
            overflow:hidden;
            color:#b8c2cb;
            font-size:.61rem;
            font-weight:950;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .sports-status{
            display:inline-flex;
            align-items:center;
            gap:.32rem;
            flex:0 0 auto;
            padding:.31rem .48rem;
            border-radius:999px;
            border:1px solid rgba(68,224,160,.18);
            background:rgba(68,224,160,.065);
            color:#79eab9;
            font-size:.48rem;
            font-weight:950;
            letter-spacing:.07em;
        }
        .sports-status i{width:.34rem;height:.34rem;border-radius:50%;background:#49e0a3}

        .sports-match__body{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);
            align-items:center;
            gap:1rem;
            padding:1rem .95rem .85rem;
        }
        .sports-team{
            display:flex;
            align-items:center;
            gap:.68rem;
            min-width:0;
        }
        .sports-team.away{
            justify-content:flex-end;
            text-align:right;
        }
        .sports-badge{
            display:grid;
            place-items:center;
            width:2.9rem;
            height:2.9rem;
            flex:0 0 auto;
            border:1px solid rgba(255,255,255,.09);
            border-radius:.78rem;
            background:linear-gradient(145deg,#17212d,#090e14);
            color:#e1e8ed;
            font-size:.57rem;
            font-weight:1000;
            letter-spacing:.05em;
            box-shadow:inset 0 1px rgba(255,255,255,.035);
        }
        .sports-team strong{
            display:block;
            overflow:hidden;
            color:#f0f4f7;
            font-size:.76rem;
            font-weight:950;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .sports-team small{
            display:block;
            margin-top:.12rem;
            color:#657483;
            font-size:.51rem;
        }
        .sports-vs{
            min-width:4.2rem;
            text-align:center;
        }
        .sports-vs strong{
            display:block;
            color:#d9e0e5;
            font-size:.59rem;
            font-weight:1000;
            letter-spacing:.05em;
        }
        .sports-vs small{
            display:block;
            margin-top:.16rem;
            color:#526171;
            font-size:.49rem;
            text-transform:uppercase;
            letter-spacing:.08em;
        }

        .sports-markets{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:.45rem;
            padding:0 .86rem .86rem;
        }
        .sports-market{
            position:relative;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.45rem;
            min-width:0;
            min-height:2.55rem;
            padding:.52rem .62rem;
            overflow:hidden;
            border:1px solid rgba(255,255,255,.065);
            border-radius:.68rem;
            background:linear-gradient(180deg,rgba(255,255,255,.027),rgba(255,255,255,.014));
            color:#9da9b5;
            text-align:left;
            cursor:pointer;
            transition:.15s;
        }
        .sports-market:after{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(110deg,transparent 20%,rgba(255,255,255,.05) 50%,transparent 80%);
            transform:translateX(-120%);
            transition:transform .45s;
        }
        .sports-market:hover:after{transform:translateX(120%)}
        .sports-market:hover{
            border-color:rgba(81,216,255,.26);
            background:rgba(81,216,255,.045);
            transform:translateY(-1px);
        }
        .sports-market.is-selected{
            border-color:rgba(245,196,81,.5);
            background:linear-gradient(180deg,rgba(245,196,81,.11),rgba(245,196,81,.045));
            color:#dbe0e5;
            box-shadow:inset 0 0 0 1px rgba(245,196,81,.06),0 6px 18px rgba(245,196,81,.05);
        }
        .sports-market__name{
            position:relative;
            z-index:1;
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            font-size:.56rem;
            font-weight:850;
        }
        .sports-market__odd{
            position:relative;
            z-index:1;
            flex:0 0 auto;
            color:#f7d477;
            font-size:.68rem;
            font-weight:1000;
        }
        .sports-market__check{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:1rem;
            height:1rem;
            border-radius:50%;
            background:rgba(245,196,81,.17);
            color:#f5d477;
            font-size:.49rem;
        }
        .sports-market:not(.is-selected) .sports-market__check{display:none}

        .sports-side{
            position:sticky;
            top:1rem;
            display:grid;
            align-content:start;
            gap:.72rem;
        }
        .sports-slip{
            overflow:hidden;
            border:1px solid rgba(245,196,81,.17);
            border-radius:1.08rem;
            background:linear-gradient(145deg,rgba(18,23,31,.985),rgba(8,12,18,.99));
            box-shadow:0 24px 60px rgba(0,0,0,.23);
        }
        .sports-slip__head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.7rem;
            padding:.9rem 1rem;
            border-bottom:1px solid rgba(255,255,255,.055);
        }
        .sports-slip__title{
            display:flex;
            align-items:center;
            gap:.5rem;
            color:#f5f8fa;
            font-size:.76rem;
            font-weight:1000;
        }
        .sports-slip__count{
            display:inline-grid;
            place-items:center;
            min-width:1.45rem;
            height:1.25rem;
            padding:0 .3rem;
            border-radius:999px;
            background:rgba(245,196,81,.11);
            color:#f5d477;
            font-size:.52rem;
        }
        .sports-clear{
            padding:.3rem .1rem;
            border:0;
            background:transparent;
            color:#697887;
            font-size:.54rem;
            font-weight:850;
            cursor:pointer;
        }
        .sports-clear:hover{color:#ff9da3}

        .sports-slip__body{padding:.15rem .95rem .95rem}
        .sports-selection{
            position:relative;
            padding:.72rem 1.85rem .72rem .05rem;
            border-bottom:1px solid rgba(255,255,255,.055);
        }
        .sports-selection:last-child{border-bottom:0}
        .sports-selection__top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.5rem;
        }
        .sports-selection strong{
            display:block;
            overflow:hidden;
            color:#edf2f5;
            font-size:.65rem;
            font-weight:950;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .sports-selection__tag{
            flex:0 0 auto;
            padding:.18rem .3rem;
            border-radius:.3rem;
            background:rgba(245,196,81,.08);
            color:#e6c86f;
            font-size:.45rem;
            font-weight:950;
        }
        .sports-selection span{
            display:block;
            margin-top:.18rem;
            color:#758494;
            font-size:.54rem;
        }
        .sports-selection b{
            display:block;
            margin-top:.32rem;
            color:#f5d477;
            font-size:.68rem;
            font-weight:1000;
        }
        .sports-selection__remove{
            position:absolute;
            top:.64rem;
            right:0;
            display:grid;
            place-items:center;
            width:1.35rem;
            height:1.35rem;
            border:1px solid rgba(255,255,255,.07);
            border-radius:50%;
            background:rgba(255,255,255,.025);
            color:#748292;
            cursor:pointer;
            transition:.15s;
        }
        .sports-selection__remove:hover{
            border-color:rgba(255,118,125,.28);
            background:rgba(255,118,125,.06);
            color:#ff9da3;
        }

        .sports-summary{
            margin-top:.65rem;
            padding:.75rem;
            border:1px solid rgba(255,255,255,.055);
            border-radius:.78rem;
            background:rgba(255,255,255,.02);
        }
        .sports-summary-row{
            display:flex;
            justify-content:space-between;
            gap:1rem;
            padding:.28rem 0;
            color:#748291;
            font-size:.56rem;
        }
        .sports-summary-row strong{color:#dfe6eb}
        .sports-potential{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:1rem;
            margin-top:.45rem;
            padding-top:.58rem;
            border-top:1px solid rgba(255,255,255,.065);
        }
        .sports-potential span{color:#8794a2;font-size:.56rem}
        .sports-potential strong{color:#7df0bd;font-size:1.02rem;font-weight:1000}

        .sports-stake-label{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:.5rem;
            margin-top:.7rem;
            color:#778695;
            font-size:.54rem;
            font-weight:850;
        }
        .sports-stake-label strong{color:#d5dde2}
        .sports-stakes{
            display:grid;
            grid-template-columns:repeat(5,minmax(0,1fr));
            gap:.4rem;
            margin-top:.42rem;
        }
        .sports-stake{
            min-height:2.15rem;
            border:1px solid rgba(255,255,255,.065);
            border-radius:.6rem;
            background:rgba(255,255,255,.025);
            color:#9ca9b6;
            font-size:.57rem;
            font-weight:950;
            cursor:pointer;
            transition:.15s;
        }
        .sports-stake--max{
            border-color:rgba(81,216,255,.22);
            background:rgba(81,216,255,.045);
            color:#7bdfff;
        }
        .sports-stake--max:hover{
            border-color:rgba(81,216,255,.45);
            background:rgba(81,216,255,.08);
        }
        .sports-stake:disabled{
            opacity:.38;
            cursor:not-allowed;
            transform:none !important;
        }
        .sports-stake:hover{
            border-color:rgba(245,196,81,.28);
            transform:translateY(-1px);
        }
        .sports-stake.is-active{
            border-color:rgba(245,196,81,.4);
            background:rgba(245,196,81,.085);
            color:#f7d477;
            box-shadow:0 0 0 1px rgba(245,196,81,.05);
        }
        .sports-submit{
            width:100%;
            min-height:3.05rem;
            margin-top:.62rem;
            border:1px solid rgba(245,196,81,.58);
            border-radius:.78rem;
            background:linear-gradient(180deg,#ffeca8,#e0aa35 56%,#8c5b0c);
            color:#281c07;
            font-size:.68rem;
            font-weight:1000;
            letter-spacing:.08em;
            text-transform:uppercase;
            box-shadow:0 4px 0 #65420a,0 10px 28px rgba(245,196,81,.08);
            cursor:pointer;
            transition:.12s;
        }
        .sports-submit:hover{filter:brightness(1.05);transform:translateY(-1px)}
        .sports-submit:active{transform:translateY(2px);box-shadow:0 2px 0 #65420a}
        .sports-submit:disabled{opacity:.55;cursor:not-allowed;filter:none;transform:none}
        .sports-error{
            margin:.45rem 0 0;
            color:#ff9da3;
            font-size:.57rem;
            line-height:1.4;
        }

        .sports-empty{
            padding:2.4rem 1rem 2.5rem;
            text-align:center;
        }
        .sports-empty__icon{
            display:grid;
            place-items:center;
            width:3rem;
            height:3rem;
            margin:0 auto;
            border:1px solid rgba(255,255,255,.07);
            border-radius:.9rem;
            background:rgba(255,255,255,.025);
            font-size:1.35rem;
        }
        .sports-empty strong{
            display:block;
            margin-top:.65rem;
            color:#e2e9ee;
            font-size:.72rem;
        }
        .sports-empty p{
            max-width:20rem;
            margin:.32rem auto 0;
            color:#697786;
            font-size:.56rem;
            line-height:1.5;
        }

        .sports-notice{
            padding:.75rem .8rem;
            border:1px solid rgba(81,216,255,.09);
            border-radius:.82rem;
            background:rgba(81,216,255,.03);
            color:#728293;
            font-size:.55rem;
            line-height:1.5;
        }
        .sports-notice__title{
            display:flex;
            align-items:center;
            gap:.38rem;
            margin-bottom:.2rem;
            color:#9ae8ff;
            font-size:.58rem;
            font-weight:950;
        }

        .sports-recent{
            padding:.92rem;
        }
        .sports-recent__head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.7rem;
        }
        .sports-recent__title{
            margin:.16rem 0 0;
            color:#eef2f6;
            font-size:.76rem;
            font-weight:1000;
        }
        .sports-recent__hint{
            color:#536270;
            font-size:.49rem;
            text-transform:uppercase;
            letter-spacing:.08em;
        }
        .sports-bet-row{
            padding:.66rem 0;
            border-bottom:1px solid rgba(255,255,255,.05);
        }
        .sports-bet-row:last-child{border-bottom:0}
        .sports-bet-row__top{
            display:flex;
            justify-content:space-between;
            gap:.7rem;
        }
        .sports-bet-row strong{
            color:#e5ecf1;
            font-size:.57rem;
            font-weight:950;
        }
        .sports-bet-row b{
            color:#f5d275;
            font-size:.58rem;
        }
        .sports-bet-row small{
            display:block;
            margin-top:.18rem;
            color:#697887;
            font-size:.51rem;
        }
        .sports-bet-row em{
            font-style:normal;
            color:#75e9b7;
        }

        @media(max-width:1080px){
            .sports-top{grid-template-columns:1fr}
            .sports-head-actions{grid-template-columns:1fr 1fr;grid-template-rows:none}
            .sports-layout{grid-template-columns:minmax(0,1fr) 20rem}
        }
        @media(max-width:900px){
            .sports-layout{grid-template-columns:1fr}
            .sports-side{position:static;grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
            .sports-slip{grid-column:1/-1}
            .sports-recent{grid-column:1}
            .sports-notice{grid-column:2}
        }
        @media(max-width:650px){
            .sports-hero{padding:1.05rem}
            .sports-title{font-size:1.72rem}
            .sports-sub{font-size:.67rem}
            .sports-head-actions{grid-template-columns:1fr}
            .sports-balance{min-width:0}
            .sports-toolbar{align-items:flex-start;flex-direction:column}
            .sports-filter-note{align-items:flex-start;flex-direction:column}
            .sports-match__body{grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);gap:.55rem;padding:.86rem .72rem .75rem}
            .sports-team{gap:.45rem}
            .sports-badge{width:2.5rem;height:2.5rem;font-size:.52rem}
            .sports-team strong{font-size:.67rem}
            .sports-vs{min-width:3rem}
            .sports-markets{grid-template-columns:1fr;padding:0 .72rem .72rem}
            .sports-market{min-height:2.62rem}
            .sports-side{grid-template-columns:1fr}
            .sports-slip,.sports-recent,.sports-notice{grid-column:auto}
            .sports-slip{position:relative}
        }
    </style>

    <div class="sports-top">
        <section class="sports-hero">
            <p class="sports-kicker">ALLINBET · SPORTS</p>
            <h1 class="sports-title">Apostas desportivas</h1>
            <p class="sports-sub">Escolhe as tuas seleções, combina as odds no boletim e acompanha os teus registos sem sair desta área.</p>

            <div class="sports-hero__chips">
                <span class="sports-chip">⚡ <strong>Odds virtuais</strong></span>
                <span class="sports-chip">🎟️ <strong>Boletim dinâmico</strong></span>
                <span class="sports-chip">🔒 <strong>Créditos sem valor monetário</strong></span>
            </div>
        </section>

        <div class="sports-head-actions">
            <button type="button"
                    class="sports-balance sports-balance--history"
                    x-on:click="$dispatch('casino-open-sports-bets')"
                    aria-haspopup="dialog"
                    aria-controls="casino-sports-bets-modal">
                <div>
                    <span>O teu histórico</span>
                    <strong>🎟️ As minhas apostas</strong>
                    <small>Abre os boletins em modal</small>
                </div>
                <span aria-hidden="true" style="font-size:1.05rem;color:#6fdfff;">→</span>
            </button>

            <div class="sports-balance">
                <div>
                    <span>Saldo disponível</span>
                    <strong>{{ number_format($balance, 0, ',', '.') }} CR</strong>
                </div>
                <span aria-hidden="true" style="font-size:1.05rem;color:#f5d477;">CR</span>
            </div>
        </div>
    </div>

    <div class="sports-toolbar">
        <div class="sports-live-indicator">
            <i></i>
            Pré-jogo · cotações de demonstração
        </div>
        <span class="sports-toolbar__meta">{{ count($matches) }} eventos disponíveis · seleciona uma odd para começar</span>
    </div>

    <div class="sports-layout">
        <main class="sports-main">
            <div class="sports-tabs" role="tablist" aria-label="Desportos">
                @foreach ([
                    'all' => '🏆 Todos',
                    'Futebol' => '⚽ Futebol',
                    'Basquetebol' => '🏀 Basquetebol',
                    'Ténis' => '🎾 Ténis',
                ] as $key => $label)
                    <button type="button"
                            class="sports-tab {{ $sport === $key ? 'is-active' : '' }}"
                            wire:click="$set('sport', '{{ $key }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="sports-filter-note">
                <span>Jogos ordenados pelos próximos horários da agenda virtual.</span>
                <strong>{{ collect($matches)->where(fn ($match) => $sport === 'all' || $match['sport'] === $sport)->count() }} disponíveis</strong>
            </div>

            <div class="sports-list">
                @forelse (collect($matches)->where(fn ($match) => $sport === 'all' || $match['sport'] === $sport) as $match)
                    @php
                        $selectedMarket = collect($slip)->firstWhere('match_id', $match['id']);
                    @endphp

                    <article class="sports-match {{ $loop->first ? 'is-featured' : '' }}">
                        <div class="sports-match__top">
                            <div class="sports-match__league">
                                <span class="sports-league-icon">
                                    {{ $match['sport'] === 'Futebol' ? '⚽' : ($match['sport'] === 'Basquetebol' ? '🏀' : '🎾') }}
                                </span>
                                <div>
                                    <span class="sports-meta">{{ $match['date'] }} · {{ $match['time'] }}</span>
                                    <span class="sports-league">{{ $match['league'] }}</span>
                                </div>
                            </div>

                            <span class="sports-status"><i></i>{{ $match['status'] }}</span>
                        </div>

                        <div class="sports-match__body">
                            <div class="sports-team">
                                <span class="sports-badge">{{ $match['homeShort'] }}</span>
                                <div>
                                    <strong>{{ $match['home'] }}</strong>
                                    <small>Casa</small>
                                </div>
                            </div>

                            <div class="sports-vs">
                                <strong>VS</strong>
                                <small>{{ $match['sport'] }}</small>
                            </div>

                            <div class="sports-team away">
                                <div>
                                    <strong>{{ $match['away'] }}</strong>
                                    <small>Fora</small>
                                </div>
                                <span class="sports-badge">{{ $match['awayShort'] }}</span>
                            </div>
                        </div>

                        <div class="sports-markets">
                            @foreach ($match['markets'] as $market)
                                @php
                                    $isSelected = $selectedMarket && $selectedMarket['market_id'] === $market['id'];
                                @endphp

                                <button type="button"
                                        class="sports-market {{ $isSelected ? 'is-selected' : '' }}"
                                        wire:click="select('{{ $match['id'] }}', '{{ $market['id'] }}')"
                                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                                    <span class="sports-market__name">{{ $market['label'] }} · {{ $market['name'] }}</span>
                                    <span class="sports-market__odd">
                                        {{ number_format($market['odd'], 2, ',', '.') }}
                                        <span class="sports-market__check" aria-hidden="true">✓</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="casino-card sports-empty">
                        <div class="sports-empty__icon">🏟️</div>
                        <strong>Não existem eventos nesta modalidade</strong>
                        <p>Experimenta outra modalidade para consultares os eventos virtuais disponíveis.</p>
                    </div>
                @endforelse
            </div>
        </main>

        <aside class="sports-side">
            <section class="sports-slip">
                <div class="sports-slip__head">
                    <div class="sports-slip__title">
                        🎟️ Boletim
                        <span class="sports-slip__count">{{ count($slip) }}</span>
                    </div>

                    @if ($slip)
                        <button type="button" class="sports-clear" wire:click="clearSlip">Limpar tudo</button>
                    @endif
                </div>

                <div class="sports-slip__body">
                    @if ($slip)
                        @foreach ($slip as $selection)
                            <div class="sports-selection">
                                <button type="button"
                                        class="sports-selection__remove"
                                        wire:click="remove('{{ $selection['match_id'] }}')"
                                        aria-label="Remover {{ $selection['selection'] }}">
                                    ×
                                </button>

                                <div class="sports-selection__top">
                                    <strong>{{ $selection['selection'] }}</strong>
                                    <span class="sports-selection__tag">{{ $selection['label'] }}</span>
                                </div>
                                <span>{{ $selection['home'] }} · {{ $selection['away'] }}</span>
                                <span>{{ $selection['date'] }} · {{ $selection['time'] }}</span>
                                <b>{{ number_format($selection['odd'], 2, ',', '.') }}</b>
                            </div>
                        @endforeach

                        @php
                            $combinedOdd = collect($slip)->reduce(fn ($carry, $selection) => $carry * (float) $selection['odd'], 1.0);
                            $roundedCombinedOdd = round($combinedOdd, 2);
                            $potential = max((int) $stake, (int) floor((int) $stake * $roundedCombinedOdd));
                        @endphp

                        <div class="sports-summary">
                            <div class="sports-summary-row">
                                <span>Odd combinada</span>
                                <strong>{{ number_format($roundedCombinedOdd, 2, ',', '.') }}</strong>
                            </div>
                            <div class="sports-summary-row">
                                <span>Aposta</span>
                                <strong>{{ number_format((int) $stake, 0, ',', '.') }} CR</strong>
                            </div>
                            <div class="sports-potential">
                                <span>Prémio potencial</span>
                                <strong>+{{ number_format($potential, 0, ',', '.') }} CR</strong>
                            </div>
                        </div>

                        <div class="sports-stake-label">
                            <span>Valor da aposta</span>
                            <strong>{{ number_format((int) $stake, 0, ',', '.') }} CR</strong>
                        </div>

                        <div class="sports-stakes">
                            @foreach ([50, 100, 250, 500] as $amount)
                                <button type="button"
                                        class="sports-stake {{ (int) $stake === $amount ? 'is-active' : '' }}"
                                        wire:click="setStake({{ $amount }})">
                                    {{ $amount }}
                                </button>
                            @endforeach
                            <button type="button"
                                    class="sports-stake sports-stake--max {{ (int) $stake === (int) $balance && (int) $balance > 0 ? 'is-active' : '' }}"
                                    wire:click="setMaxStake"
                                    wire:loading.attr="disabled"
                                    wire:target="setMaxStake"
                                    @disabled((int) $balance < 1)>
                                <span wire:loading.remove wire:target="setMaxStake">MAX</span>
                                <span wire:loading wire:target="setMaxStake">…</span>
                            </button>
                        </div>

                        <button type="button"
                                class="sports-submit"
                                wire:click="placeBet"
                                wire:loading.attr="disabled"
                                wire:target="placeBet">
                            <span wire:loading.remove wire:target="placeBet">Registar aposta · {{ number_format((int) $stake, 0, ',', '.') }} CR</span>
                            <span wire:loading wire:target="placeBet">A registar…</span>
                        </button>

                        @error('slip')
                            <p class="sports-error">{{ $message }}</p>
                        @enderror
                        @error('stake')
                            <p class="sports-error">{{ $message }}</p>
                        @enderror
                    @else
                        <div class="sports-empty">
                            <div class="sports-empty__icon">🎟️</div>
                            <strong>O boletim está vazio</strong>
                            <p>Clica numa odd dos jogos para adicionares o teu primeiro prognóstico.</p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="sports-notice">
                <div class="sports-notice__title">ℹ️ Créditos virtuais</div>
                Os eventos e odds desta área são dados de demonstração. As apostas usam apenas créditos virtuais do AllinBet e não representam dinheiro real.
            </section>

            <section class="casino-card sports-recent">
                <div class="sports-recent__head">
                    <div>
                        <p class="casino-eyebrow">HISTÓRICO RÁPIDO</p>
                        <h2 class="sports-recent__title">Últimos boletins</h2>
                    </div>
                    <span class="sports-recent__hint">5 mais recentes</span>
                </div>

                @forelse ($recentBets as $bet)
                    <div class="sports-bet-row">
                        <div class="sports-bet-row__top">
                            <strong>#{{ $bet->id }} · {{ $bet->status === 'pending' ? 'EM ABERTO' : strtoupper($bet->status) }}</strong>
                            <b>{{ number_format((float) $bet->combined_odd, 2, ',', '.') }}×</b>
                        </div>
                        <small>{{ count($bet->selections ?? []) }} seleção(ões) · {{ number_format($bet->stake, 0, ',', '.') }} CR</small>
                        <small>Prémio potencial: <em>+{{ number_format($bet->potential_payout, 0, ',', '.') }} CR</em></small>
                    </div>
                @empty
                    <p class="mt-3 text-xs text-zinc-500">Ainda não tens apostas registadas.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
