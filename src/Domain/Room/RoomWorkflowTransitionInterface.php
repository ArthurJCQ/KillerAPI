<?php

declare(strict_types=1);

namespace App\Domain\Room;

use App\Domain\Room\Entity\Room;

interface RoomWorkflowTransitionInterface
{
    public function executeTransition(Room $room, string $roomStatus): bool;
}
