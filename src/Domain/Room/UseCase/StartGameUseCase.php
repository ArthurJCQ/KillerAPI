<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Mission\UseCase\DispatchMissionsUseCase;
use App\Domain\Player\UseCase\DispatchTargetsUseCase;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\Doctrine\DoctrinePersistenceAdapter;

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
