<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<Player> */
final class DoctrinePlayerRepositoryDoctrine extends DoctrineBaseRepository implements PlayerRepository
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Player::class);
    }

    /** @return Player[] */
    public function findPlayersByRoom(Room $room): array
    {
        return $this->findBy(['room' => $room]);
    }

    /** @return Player[] */
    public function findPlayersByRoomAndName(Room $room, string $name): array
    {
        return $this->findBy(['room' => $room, 'name' => $name]);
    }
}
