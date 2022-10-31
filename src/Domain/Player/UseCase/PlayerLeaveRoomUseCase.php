<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\RoomRepository;

class PlayerLeaveRoomUseCase implements PlayerUseCase
{
    public function __construct(
        private readonly PlayerTransfersRoleAdminUseCase $playerTransfersRoleAdminUseCase,
        private readonly PlayerKilledUseCase $playerKilledUseCase,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    public function execute(Player $player): void
    {
        if (!$player->getRoom()) {
            return;
        }

        $playersByRoom = $player->getRoom()->getPlayers();

        if (\count($playersByRoom) === 1) {
            $this->roomRepository->remove($player->getRoom());
        }

        // Player leaving is considered as killed.
        $this->playerKilledUseCase->execute($player);

        // Reset to ALIVE and ROLE_PLAYER in his next room
        $player->setStatus(PlayerStatus::ALIVE);

        if ($player->isAdmin()) {
            $this->playerTransfersRoleAdminUseCase->execute($player);
        }

        $player->setRoles([Player::ROLE_PLAYER]);
    }
}
