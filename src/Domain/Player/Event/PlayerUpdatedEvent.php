<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerUpdatedEvent extends Event
{
    public function __construct(protected readonly Player $player, protected readonly ?Room $previousRoom = null)
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
