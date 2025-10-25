<?php

declare(strict_types=1);

namespace App\Domain\Mission;

interface MissionGeneratorInterface
{
    /**
     * @return array<string> Array of mission contents
     */
    public function generateMissions(int $count, ?string $theme = null): array;
}
