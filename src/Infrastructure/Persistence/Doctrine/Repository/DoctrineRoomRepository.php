<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<Room> */
final class DoctrineRoomRepository extends DoctrineBaseRepository implements RoomRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Room::class);
    }

    public function getRoomByIdOrCode(mixed $identifier): mixed
    {
        return $this->repository
            ->createQueryBuilder('r')
            ->where('r.id = :identifierId')
            ->orWhere('r.code = :identifierCode')
            ->setParameter('identifierId', is_int($identifier) ? $identifier : -1)
            ->setParameter('identifierCode', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
