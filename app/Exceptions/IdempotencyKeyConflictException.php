<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class IdempotencyKeyConflictException extends DomainException
{
    public function __construct(string $idempotencyKey)
    {
        parent::__construct("Idempotency key [{$idempotencyKey}] was already used for a different operation.");
    }
}
