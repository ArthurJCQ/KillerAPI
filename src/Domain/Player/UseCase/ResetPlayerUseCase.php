<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;

readonly class ResetPlayerUseCase implements PlayerUseCase
{
    public function execute(Player $player): void
    {
        $player->getRoom()?->removePlayer($player);
//        $player->setRoom(null);
        $player->setTarget(null);
        $player->setAssignedMission(null);
        $player->setStatus(PlayerStatus::ALIVE);

        foreach ($player->getAuthoredMissions() as $mission) {
            $player->removeAuthoredMission($mission);
        }
    }
}
