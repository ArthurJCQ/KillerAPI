<?php

declare(strict_types=1);

namespace App\Domain\Room;

use App\Domain\Room\Entity\Room;

interface RoomSpecification
{
    public function isSatisfiedBy(Room $room): bool;
}
