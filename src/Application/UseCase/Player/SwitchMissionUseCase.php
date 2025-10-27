<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\MissionSwitchAlreadyUsedException;
use App\Domain\Player\Exception\PlayerHasNoMissionException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class SwitchMissionUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly MissionGeneratorInterface $missionGenerator,
        private readonly CreateMissionUseCase $createMissionUseCase,
        private readonly MissionRepository $missionRepository,
    ) {
    }

    public function execute(Player $player): void
    {
        $room = $player->getRoom();
        $currentMission = $player->getAssignedMission();

        if ($player->getStatus() !== PlayerStatus::ALIVE) {
            throw new PlayerKilledException('PLAYER_IS_KILLED');
        }

        if ($room?->getStatus() !== Room::IN_GAME) {
            throw new RoomNotInGameException('ROOM_NOT_IN_GAME');
        }

        if ($currentMission === null) {
            throw new PlayerHasNoMissionException('PLAYER_HAS_NO_MISSION');
        }

        if ($player->hasMissionSwitchUsed()) {
            throw new MissionSwitchAlreadyUsedException('MISSION_SWITCH_ALREADY_USED');
        }

        // Try to get a mission from the secondary missions pool
        $newMission = $room->popSecondaryMission();

        if ($newMission !== null) {
            $this->logger->info('Using mission from secondary pool for player {player_id} in room {room_id}', [
                'player_id' => $player->getId(),
                'room_id' => $room->getId(),
            ]);
        } else {
            // Fallback: generate new mission if pool is empty
            $this->logger->warning('Secondary missions pool is empty for room {room_id}, generating new mission', [
                'room_id' => $room->getId(),
            ]);

            $missions = $this->missionGenerator->generateMissions(1);
            $newMissionContent = $missions[0];

            $newMission = $this->createMissionUseCase->execute($newMissionContent);
            $newMission->setRoom($room);

            $this->missionRepository->store($newMission);
        }

        // Replace the old mission with the new one
        $player->setAssignedMission($newMission);

        // Mark the switch as used
        $player->setMissionSwitchUsed(true);

        // Deduct 5 points
        $player->removePoints(5);

        // Persist changes
        $this->persistenceAdapter->flush();
    }
}
