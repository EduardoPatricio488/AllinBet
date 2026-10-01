<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Games\JetXGame;
use App\Models\GameRound;
use App\Services\ProvablyFairService;
use Mockery;
use Tests\TestCase;

final class JetXGameTest extends TestCase
{
    public function test_launch_creates_a_private_crash_point_and_starts_the_flight(): void
    {
        $provablyFair = Mockery::mock(ProvablyFairService::class);
        $provablyFair
            ->shouldReceive('integer')
            ->once()
            ->with('server-seed', 'client-seed', 0, 0, 99_999_999)
            ->andReturn(90_000_000);

        $game = new JetXGame($provablyFair);
        $round = $this->round();

        $result = $game->play($round, ['action' => 'launch']);

        self::assertFalse($result->completed);
        self::assertSame('flying', $result->result['status']);
        self::assertSame(1.00, $result->result['multiplier']);
        self::assertGreaterThanOrEqual(1.00, (float) $result->state['crash_multiplier']);
        self::assertArrayNotHasKey('crash_multiplier', $result->result);
        self::assertArrayHasKey('started_at_ms', $result->state);
    }

    public function test_tick_keeps_the_jet_flying_before_the_crash_point(): void
    {
        $game = new JetXGame(Mockery::mock(ProvablyFairService::class));
        $round = $this->round();
        $round->result = [
            'public' => [
                'status' => 'flying',
                'multiplier' => 1.00,
            ],
            'private' => [
                'crash_multiplier' => 100.00,
                'started_at_ms' => (int) round(microtime(true) * 1000),
                'last_multiplier' => 1.00,
            ],
        ];

        $result = $game->play($round, ['action' => 'tick']);

        self::assertFalse($result->completed);
        self::assertSame('flying', $result->result['status']);
        self::assertGreaterThanOrEqual(1.00, (float) $result->result['multiplier']);
    }

    public function test_cashout_before_crash_finishes_with_a_positive_payout(): void
    {
        $game = new JetXGame(Mockery::mock(ProvablyFairService::class));
        $round = $this->round(100);
        $round->result = [
            'public' => [
                'status' => 'flying',
                'multiplier' => 1.00,
            ],
            'private' => [
                'crash_multiplier' => 100.00,
                'started_at_ms' => (int) round(microtime(true) * 1000) - 2500,
                'last_multiplier' => 1.00,
            ],
        ];

        $result = $game->play($round, ['action' => 'cashout']);

        self::assertTrue($result->completed);
        self::assertSame('cashed_out', $result->result['status']);
        self::assertGreaterThanOrEqual(100, $result->payout);
        self::assertGreaterThan(1.00, (float) $result->result['cashout_multiplier']);
    }

    public function test_cashout_after_crash_returns_no_payout_and_reveals_the_crash_point(): void
    {
        $game = new JetXGame(Mockery::mock(ProvablyFairService::class));
        $round = $this->round(100);
        $round->result = [
            'public' => [
                'status' => 'flying',
                'multiplier' => 1.00,
            ],
            'private' => [
                'crash_multiplier' => 2.00,
                'started_at_ms' => (int) round(microtime(true) * 1000) - 10_000,
                'last_multiplier' => 1.00,
            ],
        ];

        $result = $game->play($round, ['action' => 'cashout']);

        self::assertTrue($result->completed);
        self::assertSame('crashed', $result->result['status']);
        self::assertSame(0, $result->payout);
        self::assertSame(2.00, $result->result['crash_multiplier']);
    }

    public function test_multiplier_growth_is_monotonic(): void
    {
        self::assertSame(1.00, JetXGame::multiplierAt(0));
        self::assertGreaterThan(JetXGame::multiplierAt(1000), JetXGame::multiplierAt(3000));
        self::assertLessThanOrEqual(2500.00, JetXGame::multiplierAt(999_999));
    }

    private function round(int $bet = 10): GameRound
    {
        $round = new GameRound();
        $round->server_seed = 'server-seed';
        $round->client_seed = 'client-seed';
        $round->nonce = 0;
        $round->bet = $bet;
        $round->result = [];

        return $round;
    }
}
