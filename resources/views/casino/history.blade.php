@extends('layouts.casino')

@section('page-title', 'Casino history')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('home') }}" class="text-sm text-zinc-400 hover:text-white" wire:navigate>← Lobby</a>
        <h1 class="text-2xl font-semibold">Histórico</h1>
        <livewire:casino.history />
    </div>
@endsection
