<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CasinoSimulationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('casino:simulate {game} {rounds}')]
#[Description('Simulate casino game return-to-player using virtual credits.')]
class CasinoSimulationCommand extends Command
{
    public function handle(CasinoSimulationService $simulation): int
    {
        try {
            $result = $simulation->run((string) $this->argument('game'), (int) $this->argument('rounds'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Game', 'Rounds', 'Wagered credits', 'Paid credits', 'Actual RTP', 'Target RTP'],
            [[
                $result['game'],
                $result['rounds'],
                $result['wagered'],
                $result['paid'],
                $this->formatBasisPoints($result['rtp_basis_points']),
                $this->formatBasisPoints($result['target_basis_points']),
            ]],
        );

        return self::SUCCESS;
    }

    private function formatBasisPoints(int $basisPoints): string
    {
        return intdiv($basisPoints, 100).'.'.str_pad((string) ($basisPoints % 100), 2, '0', STR_PAD_LEFT).'%';
    }
}
