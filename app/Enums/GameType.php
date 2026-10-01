<?php

declare(strict_types=1);

namespace App\Enums;

enum GameType: string
{
    case Coinflip = 'coinflip';
    case Dice = 'dice';
    case Roulette = 'roulette';
    case Blackjack = 'blackjack';
    case Slots = 'slots';
    case Jetx = 'jetx';
}
