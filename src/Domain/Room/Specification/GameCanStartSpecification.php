<?php

declare(strict_types=1);

namespace App\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;

final readonly class GameCanStartSpecification implements RoomSpecification
{
    public function __construct(
        private EnoughPlayerInRoomSpecification $enoughPlayerInRoomSpecification,
        private EnoughMissionInRoomSpecification $enoughMissionInRoomSpecification,
        private AllPlayersAddedMissionSpecification $allPlayersAddedMissionSpecification,
    ) {
    }

    public function isSatisfiedBy(Room $room): bool
    {
        return $this->enoughPlayerInRoomSpecification->isSatisfiedBy($room)
            && $this->enoughMissionInRoomSpecification->isSatisfiedBy($room)
            && $this->allPlayersAddedMissionSpecification->isSatisfiedBy($room);
    }
}
