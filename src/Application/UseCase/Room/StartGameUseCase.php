<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionGeneratorInterface;
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
            $mission = new Mission();
            $mission->setContent($missionContent);
            $mission->setRoom($room);
            $mission->setAuthor(null); // Generated missions have no author

            $room->addSecondaryMission($mission);
        }

        $this->logger->info('Room {room_id} has started successfully.', ['room_id' => $room->getId()]);

        $this->persistenceAdapter->flush();

        $this->notifier->notify(GameStartedNotification::to(...$room->getPlayers()));
    }
}
