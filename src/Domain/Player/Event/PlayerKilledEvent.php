<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Notifications\KillerNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerKilledEvent extends Event
{
    public function __construct(
        protected readonly Player $player,
        protected readonly ?Room $room = null,
        protected readonly ?KillerNotification $killerNotification = null,
        protected readonly bool $awardPoints = true,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRoom(): ?Room
    {
        return $this->room ?? $this->player->getRoom();
    }

    public function getKillerNotification(): ?KillerNotification
    {
        return $this->killerNotification;
    }

    public function shouldAwardPoints(): bool
    {
        return $this->awardPoints;
    }
}
