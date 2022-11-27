<?php

declare(strict_types=1);

namespace App\Domain\Room\Subscriber;

use App\Domain\Player\UseCase\ResetPlayerUseCase;
use App\Domain\Room\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class RoomDoctrineSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ResetPlayerUseCase $resetPlayerUseCase)
    {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $room = $args->getObject();

        if (!$room instanceof Room) {
            return;
        }

        foreach ($room->getPlayers() as $player) {
            $this->resetPlayerUseCase->execute($player);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }
}
