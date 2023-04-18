<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

readonly class EndGameUseCase implements RoomUseCase
{
    public function __construct(private PersistenceAdapterInterface $persistenceAdapter)
    {
    }

    public function execute(Room $room): void
    {
        $winner = $room->getAlivePlayers()[0] ?? null;

        $room->setWinner($winner);

        $winner?->setTarget(null);
        $winner?->setAssignedMission(null);

        $this->persistenceAdapter->flush();
    }
}
