<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use App\Domain\Player\Exception\PlayerCanNotJoinRoomException;
use App\Domain\Room\Entity\Room;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class ChangeRoomUseCase
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function execute(Player $player, ?Room $newRoom = null): void
    {
        if ($newRoom?->getStatus() === Room::IN_GAME) {
            throw new PlayerCanNotJoinRoomException('ROOM_ALREADY_IN_GAME');
        }

        $previousRoom = $player->getRoom();
        $previousRoom?->removePlayer($player);
        $newRoom?->addPlayer($player);

        $this->eventDispatcher->dispatch(new PlayerChangedRoomEvent($player, $previousRoom));
    }
}
