<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

final class StartGameUseCase implements RoomUseCase
{
    public function __construct(
        private readonly DispatchTargetsUseCase $dispatchTargetsUseCase,
        private readonly DispatchMissionsUseCase $dispatchMissionsUseCase,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    public function execute(Room $room): void
    {
        $this->dispatchTargetsUseCase->execute($room);
        $this->dispatchMissionsUseCase->execute($room);

        $this->persistenceAdapter->flush();
    }
}
