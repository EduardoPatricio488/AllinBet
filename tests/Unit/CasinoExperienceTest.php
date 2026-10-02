<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Games\BlackjackGame;
use App\Games\SlotsGame;
use App\Models\GameRound;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use Tests\TestCase;

final class CasinoExperienceTest extends TestCase
{
    public function test_slots_use_five_horizontal_and_five_vertical_paylines(): void
    {
        $game = new SlotsGame(new ProvablyFairService);
        $variant = config('casino.games.slots.variants.classic');
        $variant['key'] = 'classic';

        $grid = array_fill(0, 5, array_fill(0, 5, 0));
        $result = $game->settle($grid, 10, $variant);

        $this->assertCount(10, $result->result['winning_lines']);
        $this->assertSame(10, $result->result['payline_count']);
        $this->assertSame(5, count($result->result['grid']));
        $this->assertSame(5, count($result->result['grid'][0]));
    }

    public function test_slots_bonus_buy_completes_all_configured_spins_atomically(): void
    {
        $game = new SlotsGame(new ProvablyFairService);
        $round = new GameRound([
            'bet' => 100,
            'server_seed' => hash('sha256', 'allinbet-bonus-seed'),
            'client_seed' => 'allinbet-test',
            'nonce' => 7,
            'result' => [
                'public' => [],
                'private' => [
                    'slot_variant' => 'classic',
                    'bonus_buy' => true,
                    'bonus_multiplier' => 10,
                    'bonus_spin_count' => 3,
                    'bonus_base_bet' => 10,
                    'bonus_label' => 'Teste',
                ],
            ],
        ]);

        $result = $game->play($round, ['action' => 'bonus_buy']);

        $this->assertTrue($result->completed);
        $this->assertCount(3, $result->result['bonus_spin_results']);
        $this->assertSame(3, $result->result['bonus_current_spin']);
        $this->assertSame(100, $result->result['bonus_cost']);
        $this->assertSame(
            $result->result['bonus_payout'] - $result->result['bonus_cost'],
            $result->result['bonus_profit'],
        );
    }

    public function test_blackjack_deal_is_reproducible_from_same_seed_and_nonce(): void
    {
        $game = new BlackjackGame(new PayoutCalculator, new ProvablyFairService);

        $attributes = [
            'bet' => 25,
            'server_seed' => hash('sha256', 'allinbet-blackjack-seed'),
            'client_seed' => 'allinbet-test',
            'nonce' => 11,
            'result' => [],
        ];

        $first = $game->play(new GameRound($attributes), ['action' => 'start']);
        $second = $game->play(new GameRound($attributes), ['action' => 'start']);

        $this->assertSame($first->result, $second->result);
        $this->assertSame($first->state, $second->state);
        $this->assertCount(2, $first->result['player_hand']);
        $this->assertCount(2, $first->result['dealer_hand']);
    }
}
