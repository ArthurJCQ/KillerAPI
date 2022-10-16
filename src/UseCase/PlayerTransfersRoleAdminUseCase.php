<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Entity\Player;
use App\Exception\PlayerNotInRoomException;
use App\Repository\PlayerRepository;
use Symfony\Component\Security\Core\Security;

class PlayerTransfersRoleAdminUseCase
{
    public function __construct(private PlayerRepository $playerRepository, private Security $security)
    {
    }

    public function execute(?Player $player = null): void
    {
        /** @var Player $playerSession */
        $playerSession = $this->security->getUser();

        if (!$playerSession->getRoom()) {
            throw new PlayerNotInRoomException('Player is not in any room.');
        }

        $playerSession->setRoles([Player::ROLE_PLAYER]);

        // if user in session is different from user to update, it means user in session is granting admin rights to
        // the user to update.
        if ($player instanceof Player && $player->getId() !== $playerSession->getId()) {
            $player->setRoles([Player::ROLE_ADMIN]);
        }

        $playersByRoom = $this->playerRepository->findPlayersByRoom($playerSession->getRoom());

        dump($playersByRoom);

        /** @var Player[] $eligibleAdmins */
        $eligibleAdmins = array_filter(
            $playersByRoom,
            static fn(Player $playerRoom) => $playerRoom->getId() !== $playerSession->getId()
        );

        shuffle($eligibleAdmins);

        $eligibleAdmins[0]->setRoles([Player::ROLE_ADMIN]);
    }
}
