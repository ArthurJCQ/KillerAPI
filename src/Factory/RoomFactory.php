<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Player;
use App\Entity\Room;
use Marvin255\RandomStringGenerator\Generator\RandomStringGenerator;
use Symfony\Component\Security\Core\Security;

class RoomFactory
{
    public function __construct(
        private readonly RandomStringGenerator $randomStringGenerator,
        private readonly Security $security,
    ) {
    }

    public function create(): Room
    {
        /** @var Player $player */
        $player = $this->security->getUser();

        return (new Room())
            ->setCode(strtoupper($this->randomStringGenerator->alphanumeric(5)))
            ->setName(sprintf("%s's room", $player->getName()))
            ->addPlayer($player);
    }
}
