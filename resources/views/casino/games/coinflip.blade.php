@extends('layouts.casino')

@section('page-title', 'Coinflip')

@section('content')
    <x-casino.game-chrome slug="coinflip">
        <livewire:casino.coinflip />
    </x-casino.game-chrome>
@endsection
