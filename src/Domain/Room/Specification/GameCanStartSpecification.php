<?php

declare(strict_types=1);

namespace App\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;

final class GameCanStartSpecification implements RoomSpecification
{
    public function __construct(
        private readonly EnoughPlayerInRoomSpecification $enoughPlayerInRoomSpecification,
        private readonly EnoughMissionInRoomSpecification $enoughMissionInRoomSpecification,
    ) {
    }

    public function isSatisfiedBy(Room $room): bool
    {
        return $this->enoughPlayerInRoomSpecification->isSatisfiedBy($room)
            && $this->enoughMissionInRoomSpecification->isSatisfiedBy($room);
    }
}
