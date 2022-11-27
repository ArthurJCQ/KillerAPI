<?php

declare(strict_types=1);

namespace App\Domain\Player\Subscriber;

use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Event\PlayerLeftRoomEvent;
use App\Domain\Player\UseCase\PlayerKilledUseCase;
use App\Domain\Player\UseCase\ResetPlayerUseCase;
use App\Domain\Room\UseCase\RoomChangeAdminUseCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PlayerKilledUseCase $playerKilledUseCase,
        private readonly ResetPlayerUseCase $resetPlayerUseCase,
        private readonly RoomChangeAdminUseCase $roomChangeAdminUseCase,
    ) {
    }

    public function onPlayerKilled(PlayerKilledEvent $playerKilledEvent): void
    {
        $this->playerKilledUseCase->execute($playerKilledEvent->getPlayer());
    }

    public function onPlayerLeftRoom(PlayerLeftRoomEvent $playerLeftRoomEvent): void
    {
        $player = $playerLeftRoomEvent->getPlayer();
        $oldRoom = $playerLeftRoomEvent->getOldRoom();

        // Player leaving is considered as killed.
        $this->eventDispatcher->dispatch(new PlayerKilledEvent($player, $oldRoom), PlayerKilledEvent::NAME);

        if ($oldRoom->getAdmin() === $player) {
            $this->roomChangeAdminUseCase->execute($oldRoom);
        }

        // Reset player for next game, as he is leaving this one
        $this->resetPlayerUseCase->execute($player);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerKilledEvent::NAME => 'onPlayerKilled',
            PlayerLeftRoomEvent::NAME => 'onPlayerLeftRoom',
        ];
    }
}
