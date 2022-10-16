<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Mission;
use App\Entity\Room;
use App\Entity\Player;
use App\Exception\NotEnoughMissionsInRoomException;
use App\Exception\PlayerNotFoundException;

class DispatchMissionsUseCase
{
    public function execute(Room $room): void
    {
        $players = $room->getPlayers()->toArray();

        usort($players, static fn($player) => \count($player->getAuthoredMissions()));

        $missions = [];

        foreach ($players as $player) {
            $missions[] = $player->getAuthoredMissions();
        }

        foreach ($players as $player) {
            $possibleMissions = array_filter(
                $missions,
                static fn($mission) => $mission->getAuthor() !== $player->getTarget(),
            );

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
