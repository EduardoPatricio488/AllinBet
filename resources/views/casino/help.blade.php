@extends('layouts.casino')

@section('page-title', 'Ajuda e jogo responsável')

@section('content')
    <div class="max-w-3xl space-y-6">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-white" wire:navigate>← Lobby</a>
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-xs font-semibold uppercase text-emerald-300">Ajuda</p>
            <h1 class="mt-2 text-2xl font-semibold">Jogo responsável</h1>
        </div>
        <p class="text-sm text-zinc-300">Todos os saldos e resultados nesta plataforma usam créditos virtuais sem valor monetário. Não há depósitos, pagamentos ou levantamentos.</p>
        <p class="text-sm text-zinc-300">Faça pausas, acompanhe o tempo de sessão e defina limites pessoais antes de jogar. Pode suspender temporariamente ou encerrar o acesso ao jogo nas configurações da conta.</p>
        <a href="{{ route('profile.edit') }}" class="inline-flex rounded-md border border-zinc-700 px-4 py-2 text-sm font-medium hover:border-zinc-500" wire:navigate>Configurações da conta</a>
    </div>
@endsection
