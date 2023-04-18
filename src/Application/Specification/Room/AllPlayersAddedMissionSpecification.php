<?php

declare(strict_types=1);

namespace App\Application\Specification\Room;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomSpecification;

class AllPlayersAddedMissionSpecification implements RoomSpecification
{
    public function isSatisfiedBy(Room $room): bool
    {
        foreach ($room->getPlayers() as $player) {
            if (\count($player->getAuthoredMissionsInRoom()) > 0) {
                continue;
            }

            return false;
        }

        return true;
    }
}
