<?php

declare(strict_types=1);

namespace App\Domain\Room\Factory;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Marvin255\RandomStringGenerator\Generator\RandomStringGenerator;
use Symfony\Component\Security\Core\Security;

readonly class RoomFactory
{
    public function __construct(
        private RandomStringGenerator $randomStringGenerator,
        private Security $security,
        private RoomRepository $roomRepository,
    ) {
    }

    public function create(): Room
    {
        $roomCode = $this->randomStringGenerator->alphanumeric(5);

        if ($this->roomRepository->findOneBy(['code' => $roomCode]) instanceof Room) {
            return $this->create();
        }

        /** @var Player $player */
        $player = $this->security->getUser();

        return (new Room())
            ->setCode(strtoupper($roomCode))
            ->setName(sprintf("%s's room", $player->getName()))
            ->setAdmin($player)
            ->addPlayer($player);
    }
}
