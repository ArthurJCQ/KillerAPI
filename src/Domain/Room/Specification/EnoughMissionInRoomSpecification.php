<?php

declare(strict_types=1);

namespace App\Domain\Room\Specification;

use App\Domain\Mission\MissionRepository;
use App\Domain\Room\Entity\Room;

class EnoughMissionInRoomSpecification implements RoomSpecification
{
    public function __construct(private readonly MissionRepository $missionRepository)
    {
    }

    // There must be at least as much mission as players.
    // Also, at least 2 players must have added missions.
    public function isSatisfiedBy(Room $room): bool
    {
        $players = $room->getPlayers();

        $playersWithAuthoredMissions = $this->missionRepository->getMissionsByRoomAndAuthor($room);

        return \count($playersWithAuthoredMissions) > 1 && (\count($room->getMissions()) < \count($players));
    }
}
