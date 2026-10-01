<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use OverflowException;

class PayoutCalculator
{
    public function grossPayout(int $wager, int $winProbabilityBasisPoints, int $houseEdgeBasisPoints): int
    {
        if (
            $wager <= 0
            || $winProbabilityBasisPoints < 1
            || $winProbabilityBasisPoints > 10000
            || $houseEdgeBasisPoints < 0
            || $houseEdgeBasisPoints >= 10000
        ) {
            throw new InvalidArgumentException('Invalid payout calculation parameters.');
        }

        $numerator = 10000 - $houseEdgeBasisPoints;
        $divisor = $this->greatestCommonDivisor($numerator, $winProbabilityBasisPoints);
        $numerator = intdiv($numerator, $divisor);
        $denominator = intdiv($winProbabilityBasisPoints, $divisor);

        if ($wager > intdiv(PHP_INT_MAX, $numerator)) {
            throw new OverflowException('The payout exceeds the supported integer range.');
        }

        return intdiv($wager * $numerator, $denominator);
    }

    public function multiply(int $wager, int $multiplier): int
    {
        if ($wager <= 0 || $multiplier < 0) {
            throw new InvalidArgumentException('Invalid fixed payout parameters.');
        }

        if ($multiplier > 0 && $wager > intdiv(PHP_INT_MAX, $multiplier)) {
            throw new OverflowException('The payout exceeds the supported integer range.');
        }

        return $wager * $multiplier;
    }

    private function greatestCommonDivisor(int $first, int $second): int
    {
        while ($second !== 0) {
            [$first, $second] = [$second, $first % $second];
        }

        return $first;
    }
}
