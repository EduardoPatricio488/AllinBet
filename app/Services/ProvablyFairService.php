<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use LogicException;

class ProvablyFairService
{
    public function createServerSeed(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function commitment(string $serverSeed): string
    {
        return hash('sha256', $serverSeed);
    }

    public function verifyCommitment(string $serverSeed, string $commitment): bool
    {
        return hash_equals($this->commitment($serverSeed), $commitment);
    }

    public function integer(string $serverSeed, string $clientSeed, int $nonce, int $minimum, int $maximum): int
    {
        $space = 1 << 52;

        if (
            $clientSeed === ''
            || $nonce < 0
            || $minimum < 0
            || $maximum < $minimum
            || $maximum >= $space
        ) {
            throw new InvalidArgumentException('Invalid provably fair integer parameters.');
        }

        $range = $maximum - $minimum + 1;
        $limit = intdiv($space, $range) * $range;

        for ($counter = 0; $counter < 256; $counter++) {
            $digest = hash_hmac('sha256', "{$clientSeed}:{$nonce}:{$counter}", $serverSeed);
            $sample = intval(substr($digest, 0, 13), 16);

            if ($sample < $limit) {
                return $minimum + ($sample % $range);
            }
        }

        throw new LogicException('Unable to derive a provably fair result.');
    }
}
