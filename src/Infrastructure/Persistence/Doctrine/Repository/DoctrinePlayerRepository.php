<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<Player> */
final class DoctrinePlayerRepository extends DoctrineBaseRepository implements PlayerRepository
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

    /**
     * Find the killer of a player (the player who has this player as their target).
     */
    public function findKiller(Player $player): ?Player
    {
        return $this->findOneBy(['target' => $player]);
    }

    /**
     * Get the current player for a user based on the user's current room context.
     * Returns the player belonging to the user that is in the user's current room.
     */
    public function getCurrentUserPlayer(User $user): ?Player
    {
        $room = $user->getRoom();

        if ($room === null) {
            return null;
        }

        return $this->findPlayerByUserAndRoom($user, $room);
    }

    public function findPlayerByUserAndRoom(User $user, Room $room): ?Player
    {
        return $this->findOneBy([
            'user' => $user,
            'room' => $room,
        ]);
    }
}
