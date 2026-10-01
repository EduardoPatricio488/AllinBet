<?php

declare(strict_types=1);

return [
    'initial_balance' => (int) env('CASINO_INITIAL_BALANCE', 1000),
    'bet_limits' => [
        'min' => (int) env('CASINO_MIN_BET', 1),
        'max' => (int) env('CASINO_MAX_BET', 10000),
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
