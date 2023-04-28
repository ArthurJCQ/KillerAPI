<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use App\Domain\Player\PasswordRandomizer;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
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

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove, Events::prePersist];
    }
}
