<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Room;
use App\Exception\CanNotApplyRoomTransition;
use Symfony\Component\Workflow\WorkflowInterface;

class RoomStatusTransitionUseCase
{
    public const START_GAME_TRANSITION = 'start_game';
    public const END_GAME_TRANSITION = 'end_game';

    public function __construct(private readonly WorkflowInterface $roomLifecycleStateMachine)
    {
    }

    public function execute(Room $room, string $roomStatus): void
    {
        $transition = match ($roomStatus) {
            Room::IN_GAME => self::START_GAME_TRANSITION,
            Room::ENDED => self::END_GAME_TRANSITION,
            default => throw new CanNotApplyRoomTransition('Room transition does not exist.'),
        };

        try {
            $transitionSuccess = $this->roomLifecycleStateMachine->can($room, $transition);
        } catch (\DomainException $e) {
            throw new CanNotApplyRoomTransition(sprintf('Could not update room status : %s', $e->getMessage()));
        }

        if (!$transitionSuccess) {
            throw new CanNotApplyRoomTransition('Could not update room status.');
        }

        $this->roomLifecycleStateMachine->apply($room, $transition);
    }
}
