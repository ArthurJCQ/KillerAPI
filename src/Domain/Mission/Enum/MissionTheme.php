<?php

declare(strict_types=1);

namespace App\Domain\Mission\Enum;

enum MissionTheme: string
{
    case CLASSIC = 'classic';
    case SPY = 'spy';
    case FANTASY = 'fantasy';
    case SCIFI = 'scifi';
    case HORROR = 'horror';
    case COMEDY = 'comedy';
    case ADVENTURE = 'adventure';
    case MYSTERY = 'mystery';
    case IN_A_BAR = 'IN_A_BAR';
}
