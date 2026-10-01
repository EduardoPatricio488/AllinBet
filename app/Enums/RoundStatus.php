<?php

declare(strict_types=1);

namespace App\Enums;

enum RoundStatus: string
{
    case Prepared = 'prepared';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
