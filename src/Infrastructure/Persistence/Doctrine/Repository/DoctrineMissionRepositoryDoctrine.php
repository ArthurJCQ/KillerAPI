<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<Mission> */
final class DoctrineMissionRepositoryDoctrine extends DoctrineBaseRepository implements MissionRepository
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
