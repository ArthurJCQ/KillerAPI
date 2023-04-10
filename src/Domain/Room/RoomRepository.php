<?php

declare(strict_types=1);

namespace App\Domain\Room;

use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<Room> */
interface RoomRepository extends BaseRepository
{
    public function getRoomByIdOrCode(mixed $identifier): mixed;

    public function getEmptyRooms(): iterable;
}
