<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Infrastructure\Persistence\Doctrine\DoctrinePersistenceAdapter;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrinePlayerRepositoryDoctrine;

class DeletePlayerUseCase
{
    public function __construct(
        private PlayerLeaveRoomUseCase           $playerLeaveRoomUseCase,
        private DoctrinePlayerRepositoryDoctrine $playerRepository,
        private DoctrinePersistenceAdapter       $persistenceAdapter,
    ) {
    }

    public function execute(Player $player): void
    {
        $this->playerLeaveRoomUseCase->execute($player);

        $this->playerRepository->remove($player);

        $this->persistenceAdapter->flush();
    }
}
