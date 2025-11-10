<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Player;

use App\Application\UseCase\Player\PlayerKilledUseCase;
use App\Application\UseCase\Player\ResetPlayerUseCase;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use App\Domain\Player\Event\PlayerKilledEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class PlayerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PlayerKilledUseCase $playerKilledUseCase,
        private ResetPlayerUseCase $resetPlayerUseCase,
    ) {
    }

    public function onPlayerKilled(PlayerKilledEvent $playerKilledEvent): void
    {
        $this->playerKilledUseCase->execute(
            player: $playerKilledEvent->getPlayer(),
            killerNotification: $playerKilledEvent->getKillerNotification(),
            awardPoints: $playerKilledEvent->shouldAwardPoints(),
        );
    }

    public function onPlayerLeftRoom(PlayerChangedRoomEvent $playerLeftRoomEvent): void
    {
        $player = $playerLeftRoomEvent->getPlayer();
        $player->setStatus(PlayerStatus::KILLED);
        $previousRoom = $playerLeftRoomEvent->getPreviousRoom();

        // Player leaving is considered as killed.
        $this->eventDispatcher->dispatch(new PlayerKilledEvent($player, $previousRoom));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerKilledEvent::class => 'onPlayerKilled',
            PlayerChangedRoomEvent::class => 'onPlayerLeftRoom',
        ];
    }
}
