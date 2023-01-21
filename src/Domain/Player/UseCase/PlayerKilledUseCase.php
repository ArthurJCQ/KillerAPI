<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Room\Entity\Room;

class PlayerKilledUseCase implements PlayerUseCase
{
    public function execute(Player $player): void
    {
        if ($player->getRoom() === null || $player->getRoom()->getStatus() !== Room::IN_GAME) {
            return;
        }

        $killer = $player->getKiller();
        $target = $player->getTarget();
        $assignedMission = $player->getAssignedMission();

        if ($killer === null || $target === null) {
            throw new PlayerHasNoKillerOrTargetException(sprintf(
                'User %s has no killer or no target',
                $player->getId(),
            ));
        }

        $player->setTarget(null);
        $player->setAssignedMission(null);

        $killer->setTarget($target);
        $killer->setAssignedMission($assignedMission);
    }
}
