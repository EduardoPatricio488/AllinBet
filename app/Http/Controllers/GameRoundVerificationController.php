<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GameRound;
use App\Services\ProvablyFairService;
use Illuminate\View\View;

class GameRoundVerificationController extends Controller
{
    public function __invoke(GameRound $gameRound, ProvablyFairService $provablyFair): View
    {
        $serverSeed = $gameRound->revealedServerSeed();

        return view('games.fairness', [
            'gameRound' => $gameRound,
            'serverSeed' => $serverSeed,
            'commitmentVerified' => $serverSeed !== null
                && $provablyFair->verifyCommitment($serverSeed, $gameRound->server_seed_hash),
        ]);
    }
}
