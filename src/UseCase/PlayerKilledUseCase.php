<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Player;
use App\Entity\Room;
use App\Exception\PlayerHasNoKillerOrTargetException;

class PlayerKilledUseCase
{
    public function execute(Player $player): void
    {
        if ($player->getRoom() === null || $player->getRoom()->getStatus() !== Room::IN_GAME) {
            return;
        }

        $killer = $player->getKiller();
        $target = $player->getTarget();

        if ($killer === null || $target === null) {
            throw new PlayerHasNoKillerOrTargetException(sprintf(
                'User %s has no killer or no target',
                $player->getId(),
            ));
        }

        $killer->setTarget($target);
        $killer->setAssignedMission($player->getAssignedMission());

        $player->setTarget(null);
        $player->setAssignedMission(null);
    }
}
