<?php

declare(strict_types=1);

namespace App\Domain\Player\Service;

use App\Domain\Player\Entity\Player;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordRandomizer
{
    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function generate(Player $player): string
    {
        // TODO: Randomize this generator
        return $this->userPasswordHasher->hashPassword($player, '@tempP@ssw0rd');
    }
}
