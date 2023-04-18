<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\PlayerUseCase;

readonly class ResetPlayerUseCase implements PlayerUseCase
{
    public function execute(Player $player): void
    {
        $player->setTarget(null);
        $player->setAssignedMission(null);
        $player->setStatus(PlayerStatus::ALIVE);
        $player->clearMissions();
    }
}
