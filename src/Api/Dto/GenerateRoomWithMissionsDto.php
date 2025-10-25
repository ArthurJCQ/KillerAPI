<?php

declare(strict_types=1);

namespace App\Api\Dto;

use App\Domain\Mission\Enum\MissionTheme;

readonly class GenerateRoomWithMissionsDto
{
    public function __construct(
        public ?string $roomName = null,
        public ?int $missionsCount = null,
        public ?MissionTheme $theme = null,
    ) {
    }
}
