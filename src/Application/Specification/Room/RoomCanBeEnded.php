<?php

declare(strict_types=1);

namespace App\Application\Specification\Room;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomSpecification;

class RoomCanBeEnded implements RoomSpecification
{
    public function isSatisfiedBy(Room $room): bool
    {
        if ($room->getStatus() !== Room::IN_GAME) {
            return false;
        }

        return \count($room->getAlivePlayers()) <= 1 || $room->getDateEnd() < new \DateTime();
    }
}
