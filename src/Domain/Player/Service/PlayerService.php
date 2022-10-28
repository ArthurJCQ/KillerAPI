<?php

declare(strict_types=1);

namespace App\Domain\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\UseCase\PlayerKilledUseCase;
use App\Domain\Player\UseCase\PlayerLeaveRoomUseCase;
use App\Domain\Player\UseCase\PlayerTransfersRoleAdminUseCase;

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
