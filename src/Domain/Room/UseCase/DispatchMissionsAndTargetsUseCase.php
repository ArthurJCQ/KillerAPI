<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Room\Entity\Room;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DispatchMissionsAndTargetsUseCase implements RoomUseCase, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function execute(Room $room): void
    {
        $players = $room->getPlayers()->toArray();

        // Shuffle array for a more random dispatch.
        shuffle($players);

        foreach ($players as $key => $player) {
            // Next player in the array is assigned to the current player as his target.
            $target = $players[$key + 1] ?? $players[0];

            $player->setTarget($target);

            $this->logger->info('Player {player_id} was assigned {target_id} as a target', [
                'player_id' => $player->getId(),
                'target_id' => $target->getId(),
            ]);

            // Let's assign missions.
            foreach ($players as $playerMissions) {
                /**
                 * player = the player we want to assign the mission to.
                 * playerMissions = the player we want to get the mission from.
                 * If they are the same we continue, as we don't want a player to pick his own mission.
                 * If playerMissions is the target of player, we don't use his missions.
                 * If playerMissions has no missions, we skip as well.
                 */
                if (
                    $player === $playerMissions
                    || $player->getTarget() === $playerMissions
                    || \count($playerMissions->getAuthoredMissionsInRoom()) === 0
                ) {
                    continue;
                }

                // Shuffle missions so any mission has the same chance to get picked.
                $missions = $playerMissions->getAuthoredMissionsInRoom();
                shuffle($missions);

                // Dive into all playerMissions missions.
                foreach ($missions as $mission) {
                    // If mission already assigned, skip.
                    if ($mission->isAssigned()) {
                        continue;
                    }

                    // Otherwise, assign it and break to not keep assigning missions.
                    $player->setAssignedMission($mission);
                    $mission->setIsAssigned(true);

                    $this->logger->info('Player {player_id} was assigned {mission_id} as a mission.', [
                        'player_id' => $player->getId(),
                        'mission_id' => $mission->getId(),
                    ]);

                    break;
                }

                // Break to not keep trying to assign mission to the player.
                if ($player->getAssignedMission() instanceof Mission) {
                    break;
                }
            }
        }
    }
}
