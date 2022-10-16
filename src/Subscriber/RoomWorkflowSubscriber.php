<?php

declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\Room;
use App\UseCase\CanStartGameUseCase;
use App\UseCase\StartGameUseCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class RoomWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CanStartGameUseCase $canStartGameUseCase,
        private StartGameUseCase $startGameUseCase,
    ) {
    }

    public function guardStartGame(GuardEvent $event): void
    {
        /** @var Room $room */
        $room = $event->getSubject();

        try {
            $this->canStartGameUseCase->execute($room);
        } catch (\DomainException $e) {
            $event->setBlocked(true, sprintf('Can not start the game : %s', $e->getMessage()));
        }
    }

    public function completedStartGame(CompletedEvent $event): void
    {
        /** @var Room $room */
        $room = $event->getSubject();

        $this->startGameUseCase->execute($room);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.room_lifecycle.guard.start_game' => ['guardStartGame'],
            'workflow.room_lifecycle.completed.start_game' => ['completedStartGame'],
        ];
    }
}
