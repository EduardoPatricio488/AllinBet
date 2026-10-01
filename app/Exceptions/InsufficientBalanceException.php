<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class InsufficientBalanceException extends DomainException
{
    public function __construct(
        public readonly int $requestedAmount,
        public readonly int $availableBalance,
    ) {
        parent::__construct("Insufficient virtual credits: requested {$requestedAmount}, available {$availableBalance}.");
    }
}
