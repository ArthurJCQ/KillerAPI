<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerChangedRoomEvent extends Event
{
    public function __construct(protected readonly Player $player, private readonly ?Room $previousRoom)
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getPreviousRoom(): ?Room
    {
        return $this->previousRoom;
    }
}
