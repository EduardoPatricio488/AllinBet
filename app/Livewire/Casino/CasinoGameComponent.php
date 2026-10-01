<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Models\User;
use App\Services\BetService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class CasinoGameComponent extends Component
{
    #[Locked]
    public ?int $roundId = null;

    #[Locked]
    public string $roundPhase = 'ready';

    #[Locked]
    public string $serverSeedHash = '';

    #[Locked]
    public array $roundResult = [];

    #[Locked]
    public int $roundPayout = 0;

    #[Locked]
    public string $requestKey = '';

    #[Locked]
    public string $actionKey = '';

    public int|string $bet = 300;

    public string $clientSeed = '';

    public function mount(): void
    {
        $this->clientSeed = bin2hex(random_bytes(12));
        $this->requestKey = bin2hex(random_bytes(16));
        $this->actionKey = bin2hex(random_bytes(16));
    }

    protected function prepareGame(GameType $game): void
    {
        $this->resetErrorBag();
        $this->validate([
            'bet' => ['required', 'integer', 'min:1', 'max:'.(int) config('casino.bet_limits.max', 10000)],
            'clientSeed' => ['required', 'string', 'min:1', 'max:128'],
        ]);

        if (in_array($this->roundPhase, [RoundStatus::Prepared->value, RoundStatus::InProgress->value], true)) {
            return;
        }

        if ($this->roundPhase === RoundStatus::Completed->value) {
            $this->roundId = null;
            $this->roundResult = [];
            $this->roundPayout = 0;
            $this->roundPhase = 'ready';
            $this->requestKey = bin2hex(random_bytes(16));
            $this->actionKey = bin2hex(random_bytes(16));
        }

        $user = $this->authenticatedUser();

        try {
            $round = app(BetService::class)->prepare(
                $user,
                $game,
                (int) $this->bet,
                $this->clientSeed,
                $this->requestKey,
                $metadata,
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->addError('bet', $exception->getMessage());
            $this->dispatch('casino-toast', type: 'error', title: 'Aposta não permitida', message: $exception->getMessage());

            return;
        }

        $this->roundId = $round->id;
        $this->serverSeedHash = $round->server_seed_hash;
        $this->roundPhase = $round->status->value;
    }

    /** @param array<string, mixed> $input */
    protected function playGame(GameType $game, array $input = []): void
    {
        $this->resetErrorBag('game');

        if ($this->roundId === null || ! in_array($this->roundPhase, [RoundStatus::Prepared->value, RoundStatus::InProgress->value], true)) {
            $this->addError('game', 'Prepare a round before playing.');
            $this->dispatch('casino-toast', type: 'error', title: 'Prepare uma ronda', message: 'Prepare uma ronda antes de jogar.');

            return;
        }

        $user = $this->authenticatedUser();

        try {
            $round = app(BetService::class)->play($user, $this->roundId, [
                ...$input,
                'action_id' => $this->actionKey,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->addError('game', $exception->getMessage());
            $this->dispatch('casino-toast', type: 'error', title: 'Ronda não concluída', message: $exception->getMessage());

            return;
        }

        $this->roundPhase = $round->status->value;
        $this->roundResult = $round->publicResult();
        $this->roundPayout = $round->payout;
        $this->actionKey = bin2hex(random_bytes(16));

        if ($round->status === RoundStatus::Completed) {
            $this->requestKey = bin2hex(random_bytes(16));
        }

        $this->dispatch('wallet-updated');
        $this->dispatch('history-updated');

        if ($round->status === RoundStatus::Completed) {
            $this->dispatch(
                'casino-round-result',
                outcome: $round->payout > 0 ? 'win' : ($round->payout === $round->bet ? 'push' : 'loss'),
                amount: $round->payout > 0 ? $round->payout : $round->bet,
            );
        }

        if ($round->status === RoundStatus::Completed && $round->payout > 0) {
            $this->dispatch('casino-toast', type: 'success', title: 'Vitória confirmada', message: "+{$round->payout} créditos virtuais.");

            $bigWinMultiplier = max(1, (int) config('casino.big_win_multiplier', 5));

            if ($round->payout >= $round->bet * $bigWinMultiplier) {
                $this->dispatch('casino-big-win', amount: $round->payout, multiple: $bigWinMultiplier);
            }
        }
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
