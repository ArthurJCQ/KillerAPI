<?php

declare(strict_types=1);

namespace App\Domain\Player\Enum;

enum PlayerStatus: string
{
    case ALIVE = 'ALIVE';
    case KILLED = 'KILLED';
}
