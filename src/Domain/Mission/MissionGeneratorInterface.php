<?php

declare(strict_types=1);

namespace App\Domain\Mission;

use App\Domain\Mission\Enum\MissionTheme;

interface MissionGeneratorInterface
{
    /**
     * @return array<string> Array of mission contents
     */
    public function generateMissions(int $count, ?MissionTheme $theme = null): array;
}
