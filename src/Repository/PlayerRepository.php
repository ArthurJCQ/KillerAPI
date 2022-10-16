<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Room;
use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

/** @extends BaseRepository<Player> */
final class PlayerRepository extends BaseRepository
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
