<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Room;

use App\Application\UseCase\Player\ResetPlayerUseCase;
use App\Domain\Room\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::preRemove)]
readonly class RoomDoctrineListener
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
}
