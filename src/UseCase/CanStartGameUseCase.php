<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Room;
use App\Exception\NotEnoughMissionsInRoomException;
use App\Exception\NotEnoughPlayersInRoomException;
use App\Repository\MissionRepository;

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

        $missions = [];

        foreach ($players as $player) {
            $missions[] = $player->getAuthoredMissionsInRoom();
        }

        $missionsByPlayer = $this->missionRepository->getMissionsByRoomAndAuthor($room);

        if (\count($missionsByPlayer) <= 1) {
            throw new NotEnoughMissionsInRoomException('At least 2 players must add missions in room.');
        }

        if (\count($missions) < \count($players)) {
            throw new NotEnoughMissionsInRoomException('There is not enough missions in the room.');
        }

        return true;
    }
}
