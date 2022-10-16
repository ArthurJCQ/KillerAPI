<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Mission;
use App\Entity\Room;
use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

/** @extends BaseRepository<Mission> */
class MissionRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Mission::class);
    }

    /** @return ?Mission[] */
    public function findByUserId(Player $player): ?array
    {
        return $this->repository->findBy(['author' => $player]);
    }

    public function countMissionByRoom(Room $room): int
    {
            return \count($this->repository->createQueryBuilder('m')
                ->select('m')
                ->join('m.author', 'a')
                ->join('a.room', 'r')
                ->where('r = :room')
                ->setParameter('room', $room)
                ->getQuery()
                ->getArrayResult());
    }

    /** @return Mission[] */
    public function getMissionsByRoomAndAuthor(Room $room): array
    {
        /** @var Mission[] */
        return $this->repository->createQueryBuilder('m')
            ->join('m.author', 'a')
            ->join('a.room', 'r')
            ->where('r = :room')
            ->setParameter('room', $room)
            ->groupBy('m.author')
            ->getQuery()
            ->getArrayResult();
    }
}
