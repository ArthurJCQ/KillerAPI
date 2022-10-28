<?php

declare(strict_types=1);

namespace App\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Exception\NotEnoughPlayersInRoomException;
use Symfony\Component\Security\Core\Security;

class PlayerTransfersRoleAdminUseCase
{
    public function __construct(private readonly Security $security)
    {
    }

    public function execute(?Player $player = null): void
    {
        /** @var Player $playerSession */
        $playerSession = $this->security->getUser();

        if (!$playerSession->getRoom()) {
            return;
        }

        // if user in session is different from user to update, it means user in session is granting admin rights to
        // the user to update.
        if ($player instanceof Player && $player->getId() !== $playerSession->getId()) {
            $playerSession->setRoles([Player::ROLE_PLAYER]);
            $player->setRoles([Player::ROLE_ADMIN]);

            return;
        }

        $playersByRoom = $player->getRoom()?->getPlayers()->toArray();

        if (\count($playersByRoom) <= 1) {
            throw new NotEnoughPlayersInRoomException('Not enough players in room to transfer ADMIN role.');
        }

        /** @var Player[] $eligibleAdmins */
        $eligibleAdmins = array_filter(
            $playersByRoom,
            static fn(Player $playerRoom) => $playerRoom->getId() !== $playerSession->getId()
        );

        shuffle($eligibleAdmins);

        $playerSession->setRoles([Player::ROLE_PLAYER]);
        $eligibleAdmins[0]->setRoles([Player::ROLE_ADMIN]);
    }
}
