<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use App\Domain\Player\Exception\PlayerCanNotJoinRoomException;
use App\Domain\Player\PasswordRandomizer;
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
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player || !$player->getRoom()) {
            return;
        }

        $this->eventDispatcher->dispatch(new PlayerChangedRoomEvent($player, $player->getRoom()));
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

        if ($args->hasChangedField('status') && $args->getNewValue('status') === PlayerStatus::KILLED->value) {
            $this->eventDispatcher->dispatch(new PlayerKilledEvent($player));
        }
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove, Events::preUpdate, Events::prePersist];
    }
}
