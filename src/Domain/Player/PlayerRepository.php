<?php

declare(strict_types=1);

namespace App\Domain\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<Player> */
interface PlayerRepository extends BaseRepository
{
    /** @return Player[] */
    public function findPlayersByRoom(Room $room): array;

    /** @return Player[] */
    public function findPlayersByRoomAndName(Room $room, string $name): array;
}
