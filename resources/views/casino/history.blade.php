@extends('layouts.casino')

@section('page-title', 'Casino history')

@section('content')
    <div class="space-y-6">
        <div class="casino-page-hero">
            <div>
                <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-casino-gold-bright" wire:navigate>← Lobby</a>
                <p class="casino-eyebrow mt-4">A SUA MESA</p>
                <h1>Histórico</h1>
                <p class="mt-2 max-w-xl text-sm text-zinc-400">Rondas e movimentos da carteira, com verificação individual de cada resultado.</p>
            </div>
            <a href="{{ route('casino.wallet') }}" class="casino-button casino-button--secondary" wire:navigate>Abrir carteira ↗</a>
        </div>
        <div class="casino-card casino-history-wrap">
            <livewire:casino.history />
        </div>
    </div>
@endsection
