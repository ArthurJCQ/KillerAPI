<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Enum\PlayerStatus;
use App\UseCase\PlayerKilledUseCase;
use App\UseCase\PlayerLeaveRoomUseCase;
use App\UseCase\PlayerTransfersRoleAdminUseCase;

class PlayerService
{
    public function __construct(
        private readonly PlayerLeaveRoomUseCase $playerLeaveRoomUseCase,
        private readonly PlayerKilledUseCase $playerKilledUseCase,
        private readonly PlayerTransfersRoleAdminUseCase $playerTransfersRoleAdminUseCase,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function handleUpdate(array $data, Player $player): void
    {
        if (isset($data['role']) && $data['role'] === Player::ROLE_PLAYER) {
            $this->playerTransfersRoleAdminUseCase->execute($player);
        }

        if (array_key_exists('room', $data) && $player->getRoom() && $player->getRoom()->getCode() !== $data['room']) {
            $this->playerLeaveRoomUseCase->execute($player);
        }

        if (isset($data['status']) && $data['status'] === PlayerStatus::KILLED) {
            $this->playerKilledUseCase->execute($player);
        }
    }
}
