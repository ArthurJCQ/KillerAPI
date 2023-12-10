<?php

declare(strict_types=1);

namespace App\Application\Specification\Room;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomSpecification;

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
        if ($room->isGameMastered()) {
            return $this->enoughPlayerInRoomSpecification->isSatisfiedBy($room)
                && $this->enoughMissionInRoomSpecification->isSatisfiedBy($room);
        }

        return $this->enoughPlayerInRoomSpecification->isSatisfiedBy($room)
            && $this->enoughMissionInRoomSpecification->isSatisfiedBy($room)
            && $this->allPlayersAddedMissionSpecification->isSatisfiedBy($room);
    }
}
