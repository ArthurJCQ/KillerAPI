<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Mission\MissionRepository;
use App\Domain\Notifications\GameStartedNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class StartGameUseCase implements RoomUseCase, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly DispatchMissionsAndTargetsUseCase $dispatchMissionsAndTargetsUseCase,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly KillerNotifier $notifier,
        private readonly MissionGeneratorInterface $missionGenerator,
        private readonly CreateMissionUseCase $createMissionUseCase,
        private readonly MissionRepository $missionRepository,
    ) {
    }

    public function execute(Room $room): void
    {
        $this->logger->info('Room {room_id} is starting...', ['room_id' => $room->getId()]);

        $this->dispatchMissionsAndTargetsUseCase->execute($room);

        // Generate secondary missions pool: number of players * 2
        $numberOfPlayers = count($room->getAlivePlayers());
        $secondaryMissionsCount = $numberOfPlayers * 2;

        $this->logger->info('Generating {count} secondary missions for room {room_id}', [
            'count' => $secondaryMissionsCount,
            'room_id' => $room->getId(),
        ]);

        $generatedMissions = $this->missionGenerator->generateMissions($secondaryMissionsCount);

        foreach ($generatedMissions as $missionContent) {
            $mission = $this->createMissionUseCase->execute($missionContent);
            $mission->setRoom($room);
            $mission->setIsSecondaryMission(true);

            $room->addSecondaryMission($mission);
            $this->missionRepository->store($mission);
        }

        $this->logger->info('Room {room_id} has started successfully.', ['room_id' => $room->getId()]);

        $this->persistenceAdapter->flush();

        $this->notifier->notify(GameStartedNotification::to(...$room->getPlayers()));
    }
}
