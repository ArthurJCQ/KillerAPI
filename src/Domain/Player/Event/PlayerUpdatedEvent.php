<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\Entity\Player;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerUpdatedEvent extends Event
{
    public function __construct(protected readonly Player $player)
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
