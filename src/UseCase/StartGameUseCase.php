<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Room;
use App\Persistence\DoctrinePersistenceAdapter;

class StartGameUseCase
{
    public function __construct(
        private DispatchTargetsUseCase $dispatchTargetsUseCase,
        private DispatchMissionsUseCase $dispatchMissionsUseCase,
        private DoctrinePersistenceAdapter $persistenceAdapter,
    ) {
    }

    public function execute(Room $room): void
    {
        $this->dispatchTargetsUseCase->execute($room);
        $this->dispatchMissionsUseCase->execute($room);

        $this->persistenceAdapter->flush();
    }
}
