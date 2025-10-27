<?php

declare(strict_types=1);

namespace App\Domain\Mission;

use App\Domain\Mission\Enum\MissionTheme;

interface MissionGeneratorInterface
{
    /**
     * Generate random mission contents.
     *
     * @param int $count The number of missions to generate
     * @param MissionTheme|null $theme Optional theme to filter missions by
     * @return array<string> Array of mission contents
     */
    public function generateMissions(int $count, ?MissionTheme $theme = null): array;
}
