<?php

declare(strict_types=1);

namespace App\Domain\Mission;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<Mission> */
interface MissionRepository extends BaseRepository
{
    public function findByUserId(Player $player): ?array;

    public function countMissionByRoom(Room $room): int;

    public function getMissionAuthorsByRoom(Room $room): array;
}
