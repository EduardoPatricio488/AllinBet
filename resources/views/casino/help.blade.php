@extends('layouts.casino')

@section('page-title', 'Ajuda e jogo responsável')

@section('content')
    <div class="space-y-6">
        <div class="casino-page-hero">
            <div>
                <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-casino-gold-bright" wire:navigate>← Lobby</a>
                <p class="casino-eyebrow mt-4">AJUDA</p>
                <h1>Jogo responsável</h1>
            </div>
        </div>
        <div class="casino-help-grid">
            <section class="casino-card casino-help-card">
                <p class="casino-eyebrow">CRÉDITOS VIRTUAIS</p>
                <h2>Sem dinheiro real</h2>
                <p>Todos os saldos e resultados nesta plataforma usam créditos virtuais sem valor monetário. Não há depósitos, pagamentos ou levantamentos.</p>
            </section>
            <section class="casino-card casino-help-card">
                <p class="casino-eyebrow">RITMO</p>
                <h2>Faça pausas</h2>
                <p>Acompanhe o tempo de sessão e defina limites pessoais antes de jogar. Pode suspender temporariamente ou encerrar o acesso ao jogo nas configurações da conta.</p>
            </section>
            <section class="casino-card casino-help-card">
                <p class="casino-eyebrow">TRANSPARÊNCIA</p>
                <h2>Resultados verificáveis</h2>
                <p>Cada ronda guarda o compromisso da semente. Abra o histórico para confirmar o hash e o seed revelado depois do resultado.</p>
            </section>
            <section class="casino-card casino-help-card">
                <p class="casino-eyebrow">CONTA</p>
                <h2>Limites e segurança</h2>
                <p>Use uma palavra-passe forte, verifique o e-mail e ajuste o perfil quando precisar de uma pausa.</p>
                <a href="{{ route('profile.edit') }}" class="casino-button casino-button--secondary mt-4" wire:navigate>Configurações da conta</a>
            </section>
        </div>
    </div>
@endsection
