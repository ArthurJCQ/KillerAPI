<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

final class DeletePlayerUseCase implements PlayerUseCase
{
    public function __construct(
        private readonly PlayerLeaveRoomUseCase $playerLeaveRoomUseCase,
        private readonly PlayerRepository $playerRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    public function execute(Player $player): void
    {
        $this->playerLeaveRoomUseCase->execute($player);

        $this->playerRepository->remove($player);

        $this->persistenceAdapter->flush();
    }
}
