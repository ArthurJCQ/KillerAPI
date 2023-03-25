<?php

declare(strict_types=1);

namespace App\Domain\Room\Workflow;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\CanNotApplyRoomTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

readonly class RoomStatusTransitionUseCase
{
    public const START_GAME_TRANSITION = 'start_game';
    public const END_GAME_TRANSITION = 'end_game';

    public function __construct(private WorkflowInterface $roomLifecycleStateMachine)
    {
    }

    public function executeTransition(Room $room, string $roomStatus): bool
    {
        $transition = match ($roomStatus) {
            Room::IN_GAME => self::START_GAME_TRANSITION,
            Room::ENDED => self::END_GAME_TRANSITION,
            default => throw new CanNotApplyRoomTransitionException('Room transition does not exist.'),
        };

        try {
            $transitionSuccess = $this->roomLifecycleStateMachine->can($room, $transition);
        } catch (\DomainException $e) {
            throw new CanNotApplyRoomTransitionException(sprintf(
                'Could not update room status : %s',
                $e->getMessage(),
            ));
        }

        if (!$transitionSuccess) {
            return false;
        }

        $this->roomLifecycleStateMachine->apply($room, $transition);

        return true;
    }
}
