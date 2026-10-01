@extends('layouts.casino')

@section('page-title', 'Coinflip')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-white" wire:navigate>← Lobby</a>
        <div><p class="text-xs font-semibold uppercase text-amber-300">Jogo 01</p><h1 class="mt-1 text-2xl font-semibold">Coinflip</h1></div>
        <livewire:casino.coinflip />
    </div>
@endsection
