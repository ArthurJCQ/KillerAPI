<?php

declare(strict_types=1);

namespace App\Application\Specification\Room;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomSpecification;

class EnoughPlayerInRoomSpecification implements RoomSpecification
{
    /**
     * 3 players at least are needed in order to start the game
     */
    public function isSatisfiedBy(Room $room): bool
    {
        $players = $room->getAlivePlayers();

        return \count($players) > 2;
    }
}
