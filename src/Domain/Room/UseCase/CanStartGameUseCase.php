<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Mission\MissionRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\NotEnoughMissionsInRoomException;
use App\Domain\Room\Exception\NotEnoughPlayersInRoomException;

class CanStartGameUseCase
{
    public function __construct(private readonly MissionRepository $missionRepository)
    {
    }

    public function execute(Room $room): bool
    {
        $players = $room->getPlayers();

        if (\count($players) <= 2) {
            throw new NotEnoughPlayersInRoomException('Not enough players in room (min. 3)');
        }

        $missionsByPlayer = $this->missionRepository->getMissionsByRoomAndAuthor($room);

        if (\count($missionsByPlayer) <= 1) {
            throw new NotEnoughMissionsInRoomException('At least 2 players must add missions in room.');
        }

        if (\count($room->getMissions()) < \count($players)) {
            throw new NotEnoughMissionsInRoomException('There is not enough missions in the room.');
        }

        return true;
    }
}
