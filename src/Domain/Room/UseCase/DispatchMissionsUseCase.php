<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Exception\PlayerNotFoundException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\NotEnoughMissionsInRoomException;

class DispatchMissionsUseCase implements RoomUseCase
{
    public function execute(Room $room): void
    {
        $players = $room->getPlayers()->toArray();

        usort($players, static fn($player) => \count($player->getAuthoredMissionsInRoom()));

        $missions = [];

        foreach ($players as $player) {
            if (!$player->getAuthoredMissionsInRoom()) {
                continue;
            }

            $playerMissions = array_merge($missions, $player->getAuthoredMissionsInRoom());

            $missions = $playerMissions;
        }

        foreach ($players as $player) {
            // array_values reset array indexes
            $possibleMissions = array_values(array_filter(
                $missions,
                static fn($mission) => $mission->getAuthor() !== $player->getTarget(),
            ));

            $this->assignMissionToPlayer($player, $missions, $possibleMissions);
        }
    }

    /**
     * @param Mission[] $missions
     * @param Mission[] $possibleMissions
     */
    private function assignMissionToPlayer(Player $player, array &$missions, array $possibleMissions): void
    {
        if (\count($possibleMissions) <= 0) {
            throw new NotEnoughMissionsInRoomException('Not enough missions in room');
        }

        $randomMissionIndex = random_int(0, \count($possibleMissions) - 1);

        $target = $player->getTarget();

        if (!$target instanceof Player) {
            throw new PlayerNotFoundException('Could not find target on player');
        }

        $mission = $possibleMissions[$randomMissionIndex];

        // Get mission index in all missions, and remove it from the list
        $missionIndexInAllMissions = array_search($mission, $possibleMissions, true);
        unset($missions[$missionIndexInAllMissions]);

        $player->setAssignedMission($mission);
    }
}
