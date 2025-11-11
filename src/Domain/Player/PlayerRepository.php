<?php

declare(strict_types=1);

namespace App\Domain\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<Player> */
interface PlayerRepository extends BaseRepository
{
    /** @return Player[] */
    public function findPlayersByRoom(Room $room): array;

    /** @return Player[] */
    public function findPlayersByRoomAndName(Room $room, string $name): array;

    /**
     * Find the killer of a player (the player who has this player as their target).
     */
    public function findKiller(Player $player): ?Player;

    /**
     * Get the current player for a user based on the user's current room context.
     * Returns the player belonging to the user that is in the user's current room.
     */
    public function getCurrentUserPlayer(User $user): ?Player;

    public function findPlayerByUserAndRoom(User $user, Room $room): ?Player;
}
