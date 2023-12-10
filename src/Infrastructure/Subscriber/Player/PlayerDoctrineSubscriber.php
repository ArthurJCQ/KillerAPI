<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::preRemove)]
final readonly class PlayerDoctrineSubscriber
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player || !$player->getRoom()) {
            return;
        }

        $this->eventDispatcher->dispatch(new PlayerChangedRoomEvent($player, $player->getRoom()));
    }
}
