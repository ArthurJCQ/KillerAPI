<?php

declare(strict_types=1);

namespace App\Domain\Player\Subscriber;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Service\PasswordRandomizer;
use App\Domain\Player\UseCase\PlayerLeaveRoomUseCase;
use App\Domain\Room\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class PlayerDoctrineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PasswordRandomizer $randomizePassword,
        private PlayerLeaveRoomUseCase $playerLeaveRoomUseCase,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player) {
            return;
        }

        $this->playerLeaveRoomUseCase->execute($player);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player) {
            return;
        }

        $this->randomizePassword->generate($player);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player) {
            return;
        }

        if ($args->hasChangedField('room') && $args->getOldValue('room') instanceof Room) {
            $this->playerLeaveRoomUseCase->execute($player, $args->getOldValue('room'));
        }

        if ($args->hasChangedField('status') && $args->getNewValue('status') === PlayerStatus::KILLED) {
            $this->eventDispatcher->dispatch(new PlayerKilledEvent($player), PlayerKilledEvent::NAME);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove, Events::preUpdate, Events::prePersist];
    }
}
