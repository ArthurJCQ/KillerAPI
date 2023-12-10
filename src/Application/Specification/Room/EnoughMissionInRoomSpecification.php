<?php

declare(strict_types=1);

namespace App\Application\Specification\Room;

use App\Domain\Mission\MissionRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomSpecification;

class EnoughMissionInRoomSpecification implements RoomSpecification
{
    public function __construct(private readonly MissionRepository $missionRepository)
    {
    }

    // There must be at least as much mission as players.
    // Also, at least 2 players must have added missions.
    public function isSatisfiedBy(Room $room): bool
    {
        $players = $room->getAlivePlayers();

        $playersWithAuthoredMissions = $this->missionRepository->getMissionAuthorsByRoom($room);

        return \count($playersWithAuthoredMissions) > 1 && (\count($room->getMissions()) >= \count($players));
    }
}
