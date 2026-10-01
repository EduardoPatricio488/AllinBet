<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Games\Game;
use App\Games\GameRegistry;
use App\Games\GameResult;
use App\Models\GameRound;
use App\Models\User;
use App\Services\BetService;
use App\Services\ProvablyFairService;
use App\Services\ResponsibleGamingService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GameBetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepared_round_commits_seed_and_play_settles_wallet_atomically(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $provablyFair = new ProvablyFairService;
        $betService = $this->betService(new FixedCoinflipGame(150));
        $prepared = $betService->prepare($user, GameType::Coinflip, 100, 'client-seed', 'round:one');

        $this->assertSame($provablyFair->commitment($prepared->server_seed), $prepared->server_seed_hash);
        $this->assertNull($prepared->revealedServerSeed());
        $this->assertArrayNotHasKey('server_seed', $prepared->toArray());

        $completed = $betService->play($user, $prepared->id);

        $this->assertSame(RoundStatus::Completed, $completed->status);
        $this->assertSame(150, $completed->payout);
        $this->assertSame(550, $wallet->fresh()->balance);
        $this->assertSame(550, (int) $wallet->transactions()->sum('amount'));
        $this->assertTrue($provablyFair->verifyCommitment($completed->revealedServerSeed(), $completed->server_seed_hash));
    }

    public function test_a_game_failure_rolls_back_bet_and_leaves_prepared_round_retryable(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $betService = $this->betService(new FixedCoinflipGame(0, true));
        $prepared = $betService->prepare($user, GameType::Coinflip, 100, 'client-seed', 'round:rollback');

        try {
            $betService->play($user, $prepared->id);
            $this->fail('Expected the game to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Game execution failed.', $exception->getMessage());
        }

        $this->assertSame(500, $wallet->fresh()->balance);
        $this->assertSame(1, $wallet->transactions()->count());
        $this->assertSame(RoundStatus::Prepared, $prepared->fresh()->status);
    }

    public function test_replaying_prepare_and_play_does_not_charge_the_wallet_twice(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $betService = $this->betService(new FixedCoinflipGame(150));

        $prepared = $betService->prepare($user, GameType::Coinflip, 100, 'client-seed', 'round:repeat');
        $replayedPreparation = $betService->prepare($user, GameType::Coinflip, 100, 'client-seed', 'round:repeat');
        $completed = $betService->play($user, $prepared->id);
        $replayedPlay = $betService->play($user, $prepared->id);

        $this->assertSame($prepared->id, $replayedPreparation->id);
        $this->assertSame($completed->id, $replayedPlay->id);
        $this->assertSame(550, $wallet->fresh()->balance);
        $this->assertSame(3, $wallet->transactions()->count());
    }

    public function test_verification_page_shows_commitment_and_reveals_seed_after_completion(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 500);
        $betService = $this->betService(new FixedCoinflipGame(0));
        $prepared = $betService->prepare($user, GameType::Coinflip, 100, 'client-seed', 'round:verify');

        $this->actingAs($user)
            ->get(route('fairness.verify', $prepared))
            ->assertOk()
            ->assertSee($prepared->server_seed_hash)
            ->assertDontSee($prepared->server_seed);

        $completed = $betService->play($user, $prepared->id);

        $this->get(route('fairness.verify', $completed))
            ->assertOk()
            ->assertSee($completed->revealedServerSeed())
            ->assertSee('Compromisso verificado');
    }

    private function betService(Game $game): BetService
    {
        $registry = new GameRegistry;
        $registry->register($game);

        return new BetService(
            app(WalletService::class),
            $registry,
            new ProvablyFairService,
            app(ResponsibleGamingService::class),
        );
    }
}

final class FixedCoinflipGame implements Game
{
    public function __construct(private readonly int $payout, private readonly bool $shouldFail = false) {}

    public function type(): GameType
    {
        return GameType::Coinflip;
    }

    public function play(GameRound $round, array $input): GameResult
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Game execution failed.');
        }

        $outcome = (new ProvablyFairService)->integer(
            $round->server_seed,
            $round->client_seed,
            $round->nonce,
            0,
            1,
        );

        return new GameResult($this->payout, ['outcome' => $outcome]);
    }
}
