<?php

declare(strict_types=1);

namespace App\Domain\Mission\Enum;

enum MissionTheme: string
{
    case STEALTH = 'STEALTH';
    case SOCIAL = 'SOCIAL';
    case PERFORMANCE = 'PERFORMANCE';
    case CHALLENGE = 'CHALLENGE';
    case CREATIVE = 'CREATIVE';
}
