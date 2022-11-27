<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class StartGameUseCase implements RoomUseCase, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly DispatchMissionsAndTargetsUseCase $dispatchMissionsAndTargetsUseCase,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    public function execute(Room $room): void
    {
        $this->logger->info('Room {room_id} is starting...', ['room_id' => $room->getId()]);

        $this->dispatchMissionsAndTargetsUseCase->execute($room);

        $this->logger->info('Room {room_id} has started successfully.', ['room_id' => $room->getId()]);

        $this->persistenceAdapter->flush();
    }
}
