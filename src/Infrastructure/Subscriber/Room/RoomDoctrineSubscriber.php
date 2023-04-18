<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Room;

use App\Application\UseCase\Player\ResetPlayerUseCase;
use App\Domain\Room\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

readonly class RoomDoctrineSubscriber implements EventSubscriberInterface
{
    public function __construct(private ResetPlayerUseCase $resetPlayerUseCase)
    {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $room = $args->getObject();

        if (!$room instanceof Room) {
            return;
        }

        foreach ($room->getPlayers() as $player) {
            $room->removePlayer($player);
            $this->resetPlayerUseCase->execute($player);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }
}
