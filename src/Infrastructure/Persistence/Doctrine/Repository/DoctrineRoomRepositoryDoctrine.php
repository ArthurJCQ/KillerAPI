<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<Room> */
final class DoctrineRoomRepositoryDoctrine extends DoctrineBaseRepository implements RoomRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Room::class);
    }
}
