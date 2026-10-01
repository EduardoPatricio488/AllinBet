<?php

declare(strict_types=1);

return [
    'initial_balance' => (int) env('CASINO_INITIAL_BALANCE', 1000),
    'bet_limits' => [
        'min' => (int) env('CASINO_MIN_BET', 1),
        'max' => (int) env('CASINO_MAX_BET', 10000),
    ],
    'catalog' => [
        [
            'slug' => 'coinflip',
            'route' => 'casino.coinflip',
            'name' => 'Coinflip',
            'code' => '01',
            'category' => 'sorte',
            'tag' => 'ORIGINAL',
            'label' => 'Cara ou coroa',
            'blurb' => 'Escolha um lado, prepare o compromisso e lance a moeda.',
            'icon' => '◎',
        ],
        [
            'slug' => 'dice',
            'route' => 'casino.dice',
            'name' => 'Dados',
            'code' => '02',
            'category' => 'dados',
            'tag' => 'ORIGINAL',
            'label' => 'Abaixo ou acima',
            'blurb' => 'Defina o limite, o lado da curva e deixe o dado decidir.',
            'icon' => '⚄',
        ],
        [
            'slug' => 'roulette',
            'route' => 'casino.roulette',
            'name' => 'Roleta europeia',
            'code' => '03',
            'category' => 'mesas',
            'tag' => 'MESA',
            'label' => 'Roda de zero único',
            'blurb' => 'Apostas internas e externas numa roda clássica de 37 casas.',
            'icon' => '◉',
        ],
        [
            'slug' => 'blackjack',
            'route' => 'casino.blackjack',
            'name' => 'Blackjack',
            'code' => '04',
            'category' => 'cartas',
            'tag' => 'MESA',
            'label' => 'Pedir, parar ou dobrar',
            'blurb' => 'Chegue o mais perto possível de 21 sem ultrapassar o dealer.',
            'icon' => '♠',
        ],
        [
            'slug' => 'slots',
            'route' => 'casino.slots',
            'name' => 'Slots',
            'code' => '05',
            'category' => 'sorte',
            'tag' => 'ORIGINAL',
            'label' => 'Rolos e linhas de prémio',
            'blurb' => 'Três rolos, três linhas e um resultado verificável em cada giro.',
            'icon' => '✦',
        ],
    ],
    'games' => [
        'coinflip' => ['house_edge_bps' => 250],
        'dice' => [
            'house_edge_bps' => 500,
            'target_rtp_basis_points' => 9500,
        ],
        'slots' => [
            'rows' => 3,
            'columns' => 3,
            'symbol_count' => 5,
            'paylines' => [0, 1, 2],
            'paytable' => [4, 8, 12, 24, 71],
            'target_rtp_basis_points' => 9520,
        ],
    ],
];
