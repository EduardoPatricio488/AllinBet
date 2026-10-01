<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Verificação da ronda') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900">
        <main class="mx-auto max-w-2xl space-y-6 px-6 py-12">
            <header class="space-y-2">
                <p class="text-sm font-medium text-emerald-700">{{ __('Créditos virtuais — sem valor monetário') }}</p>
                <h1 class="text-2xl font-semibold">{{ __('Verificação provably fair') }}</h1>
                <p class="text-sm text-zinc-600">{{ __('Ronda #:id', ['id' => $gameRound->id]) }}</p>
            </header>

            <dl class="space-y-4 border-y border-zinc-200 py-5">
                <div>
                    <dt class="text-sm text-zinc-600">{{ __('Hash do seed do servidor') }}</dt>
                    <dd class="break-all font-mono text-sm">{{ $gameRound->server_seed_hash }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-600">{{ __('Seed do cliente') }}</dt>
                    <dd class="break-all font-mono text-sm">{{ $gameRound->client_seed }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-600">{{ __('Nonce') }}</dt>
                    <dd class="font-mono text-sm">{{ $gameRound->nonce }}</dd>
                </div>
                @if ($serverSeed !== null)
                    <div>
                        <dt class="text-sm text-zinc-600">{{ __('Seed revelado do servidor') }}</dt>
                        <dd class="break-all font-mono text-sm">{{ $serverSeed }}</dd>
                    </div>
                @endif
            </dl>

            @if ($serverSeed === null)
                <p role="status" class="text-sm text-amber-800">{{ __('O seed será revelado quando a ronda terminar.') }}</p>
            @elseif ($commitmentVerified)
                <p role="status" class="text-sm font-medium text-emerald-800">{{ __('Compromisso verificado: o hash corresponde ao seed revelado.') }}</p>
            @else
                <p role="alert" class="text-sm font-medium text-red-800">{{ __('O hash não corresponde ao seed revelado.') }}</p>
            @endif
        </main>
    </body>
</html>
