<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\SportsBet;
use App\Models\User;
use App\Services\WalletService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

final class Sports extends Component
{
    public string $sport = 'all';

    public int|string $stake = 100;

    /** @var array<int, array<string, mixed>> */
    public array $slip = [];

    public string $requestKey = '';

    public function mount(): void
    {
        $this->requestKey = bin2hex(random_bytes(16));
    }

    public function select(string $matchId, string $marketId): void
    {
        $selection = $this->findSelection($matchId, $marketId);

        if ($selection === null) {
            $this->addError('slip', 'Essa seleção já não está disponível.');

            return;
        }

        $this->resetErrorBag('slip');

        $this->slip = array_values(array_filter(
            $this->slip,
            static fn (array $item): bool => $item['match_id'] !== $matchId,
        ));

        $this->slip[] = $selection;
    }

    public function remove(string $matchId): void
    {
        $this->slip = array_values(array_filter(
            $this->slip,
            static fn (array $item): bool => $item['match_id'] !== $matchId,
        ));
    }

    public function clearSlip(): void
    {
        $this->slip = [];
        $this->resetErrorBag('slip');
    }

    public function setStake(int $amount): void
    {
        $this->stake = $amount;
    }

    public function placeBet(WalletService $walletService): void
    {
        $this->resetErrorBag();

        $validated = $this->validate([
            'stake' => ['required', 'integer', 'min:1', 'max:'.(int) config('casino.bet_limits.max', 10000)],
        ]);

        if ($this->slip === []) {
            $this->addError('slip', 'Seleciona pelo menos um prognóstico.');

            return;
        }

        $freshSlip = [];
        foreach ($this->slip as $item) {
            $selection = $this->findSelection(
                (string) ($item['match_id'] ?? ''),
                (string) ($item['market_id'] ?? ''),
            );

            if ($selection === null) {
                $this->addError('slip', 'Uma das seleções deixou de estar disponível. Atualiza o boletim.');

                return;
            }

            $freshSlip[] = $selection;
        }

        $combinedOdd = 1.0;
        foreach ($freshSlip as $item) {
            $combinedOdd *= (float) $item['odd'];
        }

        $combinedOdd = round($combinedOdd, 2);
        $stake = (int) $validated['stake'];
        $potentialPayout = max($stake, (int) floor($stake * $combinedOdd));
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        try {
            DB::transaction(function () use ($user, $walletService, $stake, $combinedOdd, $potentialPayout, $freshSlip): void {
                $bet = SportsBet::query()->create([
                    'user_id' => $user->getKey(),
                    'stake' => $stake,
                    'combined_odd' => $combinedOdd,
                    'potential_payout' => $potentialPayout,
                    'status' => 'pending',
                    'selections' => $freshSlip,
                    'idempotency_key' => $this->requestKey,
                ]);

                $walletService->debit(
                    $user,
                    $stake,
                    'sports-bet:'.$this->requestKey,
                    $bet,
                );
            });
        } catch (Throwable $exception) {
            $this->addError('stake', $exception instanceof DomainException ? $exception->getMessage() : 'Não foi possível registar a aposta.');

            return;
        }

        $this->slip = [];
        $this->requestKey = bin2hex(random_bytes(16));

        $this->dispatch('wallet-updated');
        $this->dispatch('history-updated');
        $this->dispatch(
            'casino-toast',
            type: 'success',
            title: 'Aposta registada',
            message: "+{$potentialPayout} créditos potenciais.",
        );
    }

    public function render(): View
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $wallet = $user->wallet()->first();
        $recentBets = SportsBet::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.casino.sports', [
            'matches' => $this->matches(),
            'balance' => (int) ($wallet?->balance ?? 0),
            'recentBets' => $recentBets,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matches(): array
    {
        return [
            [
                'id' => 'slb-fcp',
                'sport' => 'Futebol',
                'league' => 'Portugal · Prime',
                'date' => 'Hoje',
                'time' => '19:30',
                'home' => 'Benfica',
                'away' => 'FC Porto',
                'homeShort' => 'SLB',
                'awayShort' => 'FCP',
                'status' => 'PRÉ-JOGO',
                'markets' => [
                    ['id' => 'home', 'label' => '1', 'name' => 'Benfica', 'odd' => 1.92],
                    ['id' => 'draw', 'label' => 'X', 'name' => 'Empate', 'odd' => 3.55],
                    ['id' => 'away', 'label' => '2', 'name' => 'FC Porto', 'odd' => 3.15],
                ],
            ],
            [
                'id' => 'ars-mci',
                'sport' => 'Futebol',
                'league' => 'Inglaterra · Elite',
                'date' => 'Hoje',
                'time' => '20:00',
                'home' => 'Arsenal',
                'away' => 'Manchester City',
                'homeShort' => 'ARS',
                'awayShort' => 'MCI',
                'status' => 'PRÉ-JOGO',
                'markets' => [
                    ['id' => 'home', 'label' => '1', 'name' => 'Arsenal', 'odd' => 2.18],
                    ['id' => 'draw', 'label' => 'X', 'name' => 'Empate', 'odd' => 3.40],
                    ['id' => 'away', 'label' => '2', 'name' => 'Manchester City', 'odd' => 2.86],
                ],
            ],
            [
                'id' => 'lakers-celtics',
                'sport' => 'Basquetebol',
                'league' => 'NBA · Showcase',
                'date' => 'Hoje',
                'time' => '22:30',
                'home' => 'Lakers',
                'away' => 'Celtics',
                'homeShort' => 'LAL',
                'awayShort' => 'BOS',
                'status' => 'PRÉ-JOGO',
                'markets' => [
                    ['id' => 'home', 'label' => 'Casa', 'name' => 'Lakers', 'odd' => 1.78],
                    ['id' => 'away', 'label' => 'Fora', 'name' => 'Celtics', 'odd' => 2.05],
                ],
            ],
            [
                'id' => 'nadal-sinner',
                'sport' => 'Ténis',
                'league' => 'Masters · Court',
                'date' => 'Amanhã',
                'time' => '15:00',
                'home' => 'Nadal',
                'away' => 'Sinner',
                'homeShort' => 'NAD',
                'awayShort' => 'SIN',
                'status' => 'PRÉ-JOGO',
                'markets' => [
                    ['id' => 'home', 'label' => '1', 'name' => 'Nadal', 'odd' => 2.72],
                    ['id' => 'away', 'label' => '2', 'name' => 'Sinner', 'odd' => 1.47],
                ],
            ],
            [
                'id' => 'sporting-braga',
                'sport' => 'Futebol',
                'league' => 'Portugal · Prime',
                'date' => 'Amanhã',
                'time' => '18:15',
                'home' => 'Sporting CP',
                'away' => 'SC Braga',
                'homeShort' => 'SCP',
                'awayShort' => 'SCB',
                'status' => 'PRÉ-JOGO',
                'markets' => [
                    ['id' => 'home', 'label' => '1', 'name' => 'Sporting CP', 'odd' => 1.74],
                    ['id' => 'draw', 'label' => 'X', 'name' => 'Empate', 'odd' => 3.85],
                    ['id' => 'away', 'label' => '2', 'name' => 'SC Braga', 'odd' => 4.40],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function findSelection(string $matchId, string $marketId): ?array
    {
        foreach ($this->matches() as $match) {
            if ($match['id'] !== $matchId) {
                continue;
            }

            foreach ($match['markets'] as $market) {
                if ($market['id'] !== $marketId) {
                    continue;
                }

                return [
                    'match_id' => $match['id'],
                    'market_id' => $market['id'],
                    'sport' => $match['sport'],
                    'league' => $match['league'],
                    'date' => $match['date'],
                    'time' => $match['time'],
                    'home' => $match['home'],
                    'away' => $match['away'],
                    'selection' => $market['name'],
                    'label' => $market['label'],
                    'odd' => (float) $market['odd'],
                ];
            }
        }

        return null;
    }
}
