@extends('layouts.casino')

@section('page-title', 'Dice')

@section('content')
    <x-casino.game-chrome slug="dice">
        <livewire:casino.dice />
    </x-casino.game-chrome>
@endsection
