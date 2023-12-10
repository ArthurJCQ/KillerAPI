<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

readonly class RoomChangeAdminUseCase implements RoomUseCase
{
    public function __construct(private PersistenceAdapterInterface $persistenceAdapter)
    {
    }

    public function execute(Room $room): void
    {
        /** @var ?Player $admin */
        $admin = $room->getAdmin();
        $playersInRoom = $room->getPlayers()->toArray();

        if (!$playersInRoom) {
            return;
        }

        /** @var Player[] $eligibleAdmins */
        $eligibleAdmins = array_filter(
            $playersInRoom,
            static fn(Player $playerRoom) => $playerRoom->getId() !== $admin?->getId()
        );

        shuffle($eligibleAdmins);

        $newAdmin = array_values($eligibleAdmins)[0] ?? null;
        $room->setAdmin($newAdmin);
        $newAdmin?->setRoles(['ROLE_ADMIN']);

        $this->persistenceAdapter->flush();
    }
}
