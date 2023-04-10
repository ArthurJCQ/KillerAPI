<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerLeftRoomEvent;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class PlayerLeaveRoomUseCase implements PlayerUseCase
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function execute(Player $player, ?Room $oldRoom = null): void
    {
        if (!$oldRoom) {
            return;
        }

        $playersByRoom = $oldRoom->getPlayers();

        if ($playersByRoom->count() === 1) {
            // If no player left after this one, do not handle any logic in it afterwards.
            $oldRoom = null;
        }

        $this->eventDispatcher->dispatch(new PlayerLeftRoomEvent($player, $oldRoom), PlayerLeftRoomEvent::NAME);
    }
}
