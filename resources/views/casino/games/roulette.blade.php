@extends('layouts.casino')

@section('page-title', 'European roulette')

@section('content')
    <x-casino.game-chrome slug="roulette">
        <livewire:casino.roulette />
    </x-casino.game-chrome>
@endsection
