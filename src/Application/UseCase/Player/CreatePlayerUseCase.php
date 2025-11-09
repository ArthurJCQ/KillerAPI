<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

readonly class CreatePlayerUseCase
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    /**
     * Create a new player for a user in a room.
     *
     * Note: This use case does NOT flush changes. The caller is responsible for flushing.
     *
     * @param User $user The user who will own this player
     * @param Room|null $room The room the player will join (null for creating a player without a room)
     * @param string|null $name The player's name (defaults to user's name if not provided)
     * @param string|null $avatar The player's avatar (defaults to user's avatar if not provided)
     * @return Player The created player
     */
    public function execute(
        User $user,
        ?Room $room = null,
        ?string $name = null,
        ?string $avatar = null,
    ): Player {
        $player = new Player();
        $player->setName($name ?? $user->getName());
        $player->setAvatar($avatar ?? $user->getAvatar());
        $player->setUser($user);

        if ($room !== null) {
            $player->setRoom($room);
        }

        $this->playerRepository->store($player);

        return $player;
    }
}
