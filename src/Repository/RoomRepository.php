<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/** @extends BaseRepository<Room> */
class RoomRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Room::class);
    }
}
