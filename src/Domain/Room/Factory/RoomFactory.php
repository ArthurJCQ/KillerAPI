<?php

declare(strict_types=1);

namespace App\Domain\Room\Factory;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
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
