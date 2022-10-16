<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Player;
use App\Persistence\DoctrinePersistenceAdapter;
use App\Repository\PlayerRepository;

class DeletePlayerUseCase
{
    public function __construct(
        private PlayerLeaveRoomUseCase $playerLeaveRoomUseCase,
        private PlayerRepository $playerRepository,
        private DoctrinePersistenceAdapter $persistenceAdapter,
    ) {
    }

    public function execute(Player $player, bool $isAdmin): void
    {
        $this->playerLeaveRoomUseCase->execute($player, $isAdmin);

        $this->playerRepository->remove($player);

        $this->persistenceAdapter->flush();
    }
}
