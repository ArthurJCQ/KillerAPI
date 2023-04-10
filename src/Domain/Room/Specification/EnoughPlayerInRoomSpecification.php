<?php

declare(strict_types=1);

namespace App\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;

class EnoughPlayerInRoomSpecification implements RoomSpecification
{
    // 3 players at least are needed in order to start the game
    public function isSatisfiedBy(Room $room): bool
    {
        $players = $room->getPlayers();

        return $players->count() > 2;
    }
}
