<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;

class ResetPlayerUseCase implements PlayerUseCase
{
    public function __construct(private readonly MissionRepository $missionRepository)
    {
    }

    public function execute(Player $player): void
    {
        $player->setRoom(null);
        $player->setTarget(null);
        $player->setStatus(PlayerStatus::ALIVE);

        foreach ($player->getAuthoredMissions() as $mission) {
            $player->removeAuthoredMission($mission);
            $this->missionRepository->remove($mission);
        }
    }
}
