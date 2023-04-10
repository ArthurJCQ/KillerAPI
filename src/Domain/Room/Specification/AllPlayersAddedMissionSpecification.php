<?php

declare(strict_types=1);

namespace App\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;

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
