<?php

declare(strict_types=1);

namespace App\Domain\Room\Subscriber;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\Specification\GameCanStartSpecification;
use App\Domain\Room\UseCase\StartGameUseCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

final class RoomWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private GameCanStartSpecification $gameCanStartSpecification,
        private StartGameUseCase $startGameUseCase,
    ) {
    }

    public function guardStartGame(GuardEvent $event): void
    {
        /** @var Room $room */
        $room = $event->getSubject();

        if ($this->gameCanStartSpecification->isSatisfiedBy($room)) {
            return;
        }

        $event->setBlocked(true, 'Can not start the game : Not enough player or mission in room.');
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
