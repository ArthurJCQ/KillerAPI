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

    public function execute(Room $room, ?Player $newAdmin = null): void
    {
        /** @var ?Player $admin */
        $admin = $room->getAdmin();
        $playersInRoom = $room->getPlayers()->toArray();

        if (!$playersInRoom) {
            return;
        }

        if ($newAdmin) {
            $player = array_filter($playersInRoom, static fn (Player $p) => $p->getName() === $newAdmin->getName());

            if (!$player) {
                throw new \InvalidArgumentException('Invalid new admin player');
            }

            $this->saveNewAdmin($room, $newAdmin);

            return;
        }

        /** @var Player[] $eligibleAdmins */
        $eligibleAdmins = array_filter(
            $playersInRoom,
            static fn (Player $playerRoom) => $playerRoom->getId() !== $admin?->getId(),
        );

        shuffle($eligibleAdmins);

        $newAdmin = $eligibleAdmins[0] ?? null;

        $this->saveNewAdmin($room, $newAdmin);
    }

    private function saveNewAdmin(Room $room, ?Player $newAdmin = null): void
    {
        $room->setAdmin($newAdmin);
        $newAdmin?->setRoles(['ROLE_ADMIN']);

        $this->persistenceAdapter->flush();
    }
}
