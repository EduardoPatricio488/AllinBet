@extends('layouts.casino')

@section('page-title', 'Blackjack')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-white" wire:navigate>← Lobby</a>
        <div><p class="text-xs font-semibold uppercase text-sky-300">Jogo 04</p><h1 class="mt-1 text-2xl font-semibold">Blackjack</h1></div>
        <livewire:casino.blackjack />
    </div>
@endsection
