<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;

readonly class CreatePlayerUseCase
{
    public function __construct(private PlayerRepository $playerRepository)
    {
    }

    /**
     * Create a new player for a user in a room.
     *
     * Note: This use case does NOT flush changes. The caller is responsible for flushing.
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
