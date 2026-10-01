@extends('layouts.casino')

@section('page-title', 'Apostas desportivas')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="casino-card p-8 text-center sm:p-12">
            <div class="text-5xl" aria-hidden="true">⚽</div>
            <p class="casino-eyebrow mt-5">ALLINBET · SPORTS</p>
            <h1 class="mt-2 text-3xl font-black text-white">Apostas desportivas</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-zinc-400">
                A secção de apostas desportivas está preparada no AllinBet e será disponibilizada aqui.
            </p>
            <a href="{{ route('home') }}" class="casino-button casino-button--primary mt-6 inline-flex" wire:navigate>
                Voltar ao Casino
            </a>
        </div>
    </div>
@endsection
