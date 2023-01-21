<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerLeftRoomEvent extends Event
{
    public const NAME = 'player.left.room';

    public function __construct(protected readonly Player $player, private readonly ?Room $oldRoom)
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getOldRoom(): ?Room
    {
        return $this->oldRoom;
    }
}
