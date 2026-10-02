<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Games\BlackjackGame;
use App\Games\GameRegistry;
use App\Models\User;
use App\Services\BetService;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use App\Services\ResponsibleGamingService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlackjackGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_hit_stand_and_replayed_actions_keep_hand_state_on_the_server(): void
    {
        $game = new FixedDeckBlackjackGame(app(PayoutCalculator::class), [
            $this->card(10),
            $this->card(5),
            $this->card(6),
            $this->card(10),
            $this->card(5),
            $this->card(2),
        ]);
        $betService = $this->betService($game);
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $prepared = $betService->prepare($user, GameType::Blackjack, 100, 'client-seed', 'blackjack:hit');

        $started = $betService->play($user, $prepared->id, ['action' => 'start', 'action_id' => 'deal-1']);
        $startReplay = $betService->play($user, $prepared->id, ['action' => 'start', 'action_id' => 'deal-1']);

        $this->assertSame(RoundStatus::InProgress, $started->status);
        $this->assertSame(16, $started->publicResult()['player_total']);
        $this->assertSame(['hidden' => true], $started->publicResult()['dealer_hand'][1]);
        $this->assertArrayNotHasKey('result', $started->toArray());
        $this->assertSame($started->id, $startReplay->id);
        $this->assertSame(2, $wallet->transactions()->count());

        $completed = $betService->play($user, $prepared->id, ['action' => 'hit', 'action_id' => 'hit-1']);

        $this->assertSame(RoundStatus::Completed, $completed->status);
        $this->assertSame('player', $completed->publicResult()['outcome']);
        $this->assertSame(600, $wallet->fresh()->balance);
        $this->assertSame(600, (int) $wallet->transactions()->sum('amount'));
    }

    public function test_double_action_debits_extra_bet_once_and_pays_from_total_wager(): void
    {
        $game = new FixedDeckBlackjackGame(app(PayoutCalculator::class), [
            $this->card(10),
            $this->card(6),
            $this->card(6),
            $this->card(10),
            $this->card(5),
            $this->card(2),
        ]);
        $betService = $this->betService($game);
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $prepared = $betService->prepare($user, GameType::Blackjack, 100, 'client-seed', 'blackjack:double');
        $betService->play($user, $prepared->id, ['action' => 'start', 'action_id' => 'deal-2']);

        $completed = $betService->play($user, $prepared->id, ['action' => 'double', 'action_id' => 'double-1']);
        $replay = $betService->play($user, $prepared->id, ['action' => 'double', 'action_id' => 'double-1']);

        $this->assertSame(400, $completed->payout);
        $this->assertSame($completed->id, $replay->id);
        $this->assertSame(700, $wallet->fresh()->balance);
        $this->assertSame(4, $wallet->transactions()->count());
    }

    /** @return array{rank: int, suit: string} */
    private function card(int $rank): array
    {
        return ['rank' => $rank, 'suit' => 'hearts'];
    }

    private function betService(BlackjackGame $game): BetService
    {
        return new BetService(
            app(WalletService::class),
            new GameRegistry([$game]),
            new ProvablyFairService,
            app(ResponsibleGamingService::class),
        );
    }
}

final class FixedDeckBlackjackGame extends BlackjackGame
{
    /** @param array<int, array{rank: int, suit: string}> $drawOrder */
    public function __construct(PayoutCalculator $payoutCalculator, private readonly array $drawOrder)
    {
        parent::__construct($payoutCalculator, app(ProvablyFairService::class));
    }

    protected function shuffledDeck(): array
    {
        return array_reverse($this->drawOrder);
    }
}
