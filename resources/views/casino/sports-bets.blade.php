@extends('layouts.casino')

@section('page-title', 'Apostas desportivas')

@section('content')
    <div x-data x-init="$nextTick(() => $dispatch('casino-open-sports-bets'))">
        <livewire:casino.sports />
    </div>
@endsection
