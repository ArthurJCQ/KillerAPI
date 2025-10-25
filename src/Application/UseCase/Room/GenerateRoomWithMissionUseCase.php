<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Mission\Enum\MissionTheme;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Use case for creating a game-mastered room with AI-generated missions
 *
 * This use case handles:
 * 1. Creating a new room with game master mode enabled
 * 2. Adding the game master as the room admin
 * 3. Generating missions using AI
 * 4. Associating missions with the room and game master
 */
class GenerateRoomWithMissionUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const int DEFAULT_MISSIONS_COUNT = 10;

    public function __construct(
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly MissionGeneratorInterface $missionGenerator,
        private readonly CreateRoomUseCase $createRoomUseCase,
        private readonly CreateMissionUseCase $createMissionUseCase,
    ) {
        $this->logger = new NullLogger();
    }

    public function execute(
        string $roomName,
        Player $gameMaster,
        int $missionsCount = self::DEFAULT_MISSIONS_COUNT,
        ?MissionTheme $theme = null,
    ): Room {
        $this->logger?->info('Creating game-mastered room with AI missions', [
            'room_name' => $roomName,
            'game_master_id' => $gameMaster->getId(),
            'missions_count' => $missionsCount,
            'theme' => $theme,
        ]);

        $room = null;

        try {
            // Step 1: Create the room with game master mode enabled
            $room = $this->createRoomUseCase->execute($gameMaster, $roomName, true);
            // Step 2: Generate missions using AI
            $missionContents = $this->missionGenerator->generateMissions($missionsCount, $theme);

            // Step 3: Create and associate missions with the room
            $this->createMissions($missionContents, $room, $gameMaster);

            // Step 4: Persist everything
            $this->persistenceAdapter->flush();

            $this->logger?->info('Game-mastered room created successfully', [
                'room_id' => $room->getId(),
                'missions_count' => \count($missionContents),
            ]);

            return $room;
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to create game-mastered room with AI missions', [
                'room_id' => $room ? $room->getId() : 'N/A',
                'error' => $e->getMessage(),
            ]);

            $gameMaster->setRoom(null);
            $this->persistenceAdapter->flush();

            throw new \RuntimeException(
                sprintf('Failed to create game-mastered room: %s', $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * @param array<string> $missionContents
     */
    private function createMissions(array $missionContents, Room $room, Player $gameMaster): void
    {
        foreach ($missionContents as $index => $content) {
            $this->createMissionUseCase->execute($content, $gameMaster);

            $this->logger?->debug('Mission created', [
                'mission_index' => $index + 1,
                'room_id' => $room->getId(),
            ]);
        }

        $this->logger?->info('All missions created and associated with room', [
            'room_id' => $room->getId(),
            'missions_count' => \count($missionContents),
        ]);
    }
}
