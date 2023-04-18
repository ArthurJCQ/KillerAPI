<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerKilledEvent extends Event
{
    public function __construct(protected readonly Player $player, protected readonly ?Room $room = null)
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRoom(): ?Room
    {
        return $this->room ?? $this->player->getRoom();
    }
}
