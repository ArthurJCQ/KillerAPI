<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Room;

use App\Application\UseCase\Room\RoomChangeAdminUseCase;
use App\Domain\Player\Event\PlayerUpdatedEvent;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomWorkflowTransitionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class RoomSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RoomChangeAdminUseCase $roomChangeAdminUseCase,
        private RoomWorkflowTransitionInterface $roomStatusTransitionUseCase,
    ) {
    }

    public function tryToEndRoomAfterPlayerUpdate(PlayerUpdatedEvent $playerUpdatedEvent): void
    {
        $room = $playerUpdatedEvent->getPlayer()->getRoom();

        if ($room) {
            // Try to end room after player update.
            $this->roomStatusTransitionUseCase->executeTransition($room, Room::ENDED);
        }
    }

    public function updateAdminIfHeLeft(PlayerUpdatedEvent $playerLeftRoomEvent): void
    {
        $previousRoom = $playerLeftRoomEvent->getPreviousRoom();

        if (!$previousRoom) {
            return;
        }

        if (!$previousRoom->getAdmin()) {
            $this->roomChangeAdminUseCase->execute($previousRoom);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerUpdatedEvent::class => [['tryToEndRoomAfterPlayerUpdate'], ['updateAdminIfHeLeft']],
        ];
    }
}
