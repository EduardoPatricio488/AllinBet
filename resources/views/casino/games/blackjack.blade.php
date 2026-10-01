@extends('layouts.casino')

@section('page-title', 'Blackjack')

@section('content')
    <x-casino.game-chrome slug="blackjack">
        <livewire:casino.blackjack />
    </x-casino.game-chrome>
@endsection
