<?php

declare(strict_types=1);

namespace App\Domain\Room\UseCase;

use App\Domain\Room\Entity\Room;

interface RoomUseCase
{
    public function execute(Room $room): void;
}
