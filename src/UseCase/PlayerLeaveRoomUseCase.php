<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Player;
use App\Enum\PlayerStatus;

class PlayerLeaveRoomUseCase
{
    public function __construct(
        private PlayerTransfersRoleAdminUseCase $playerTransfersRoleAdminUseCase,
        private PlayerKilledUseCase $playerKilledUseCase,
    ) {
    }

    public function execute(Player $player): void
    {
        if ($player->isAdmin()) {
            $this->playerTransfersRoleAdminUseCase->execute($player);
            $player->setRoles([Player::ROLE_PLAYER]);
        }

        // Player leaving is considered as killed.
        $this->playerKilledUseCase->execute($player);

        // Reset to ALIVE in his next room
        $player->setStatus(PlayerStatus::ALIVE);
    }
}
