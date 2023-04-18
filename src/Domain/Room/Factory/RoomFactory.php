<?php

declare(strict_types=1);

namespace App\Domain\Room\Factory;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Bundle\SecurityBundle\Security;

readonly class RoomFactory
{
    public function __construct(private Security $security)
    {
    }

    public function create(): Room
    {
        /** @var Player $player */
        $player = $this->security->getUser();

        return (new Room())
            ->setName(sprintf("%s's room", $player->getName()))
            ->addPlayer($player);
    }
}
