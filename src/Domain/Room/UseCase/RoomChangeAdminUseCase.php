<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;

class RoomChangeAdminUseCase implements RoomUseCase
{
    public function execute(Room $room): void
    {
        /** @var Player $admin */
        $admin = $room->getAdmin();
        $playersInRoom = $room->getPlayers()->toArray();

        if (!$playersInRoom || \count($playersInRoom) <= 1) {
            return;
        }

        /** @var Player[] $eligibleAdmins */
        $eligibleAdmins = array_filter(
            $playersInRoom,
            static fn(Player $playerRoom) => $playerRoom->getId() !== $admin->getId()
        );

        shuffle($eligibleAdmins);

        $room->setAdmin($eligibleAdmins[0]);
    }
}
