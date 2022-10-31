<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Room\Entity\Room;

final class DispatchTargetsUseCase implements RoomUseCase
{
    public function execute(Room $room): void
    {
        $players = $room->getPlayers()->toArray();

        shuffle($players);

        foreach ($players as $key => $player) {
            isset($players[$key + 1])
                ? $player->setTarget($players[$key + 1])
                : $player->setTarget($players[0]);
        }
    }
}
