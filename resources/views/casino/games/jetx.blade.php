@extends('layouts.casino')

@section('page-title', 'JetX')

@section('content')
    <x-casino.game-chrome slug="jetx">
        <livewire:casino.jet-x />
    </x-casino.game-chrome>
@endsection
