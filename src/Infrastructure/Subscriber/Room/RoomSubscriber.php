<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscriber\Room;

use App\Application\UseCase\Room\RoomChangeAdminUseCase;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
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

    public function tryToEndPlayerPreviousRoom(PlayerChangedRoomEvent $playerUpdatedEvent): void
    {
        $room = $playerUpdatedEvent->getPreviousRoom();

        if (!$room) {
            return;
        }

        // Try to end room after player left room.
        $this->endRoomTransition($room);
    }

    public function tryToEndRoom(PlayerUpdatedEvent $playerUpdatedEvent): void
    {
        $room = $playerUpdatedEvent->getPlayer()->getRoom();

        if (!$room) {
            return;
        }

        // Try to end room after player update.
        $this->endRoomTransition($room);
    }

    public function updateAdminIfHeLeft(PlayerChangedRoomEvent $playerLeftRoomEvent): void
    {
        $previousRoom = $playerLeftRoomEvent->getPreviousRoom();
        $player = $playerLeftRoomEvent->getPlayer();

        if (!$previousRoom) {
            return;
        }

        if (!$player->isAdmin()) {
            return;
        }

        $this->roomChangeAdminUseCase->execute($previousRoom);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerChangedRoomEvent::class => [['tryToEndPlayerPreviousRoom'], ['updateAdminIfHeLeft']],
            PlayerUpdatedEvent::class => 'tryToEndRoom',
        ];
    }

    private function endRoomTransition(Room $room): void
    {
        $this->roomStatusTransitionUseCase->executeTransition($room, Room::ENDED);
    }
}
