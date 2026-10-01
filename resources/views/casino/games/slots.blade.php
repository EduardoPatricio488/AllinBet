@extends('layouts.casino')

@section('page-title', 'Slots')

@section('content')
    <x-casino.game-chrome slug="slots">
        <livewire:casino.slots />
    </x-casino.game-chrome>
@endsection
