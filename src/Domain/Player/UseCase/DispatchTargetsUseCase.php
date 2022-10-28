<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Room\Entity\Room;

class DispatchTargetsUseCase
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