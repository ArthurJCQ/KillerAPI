<?php

declare(strict_types=1);

namespace App\Domain\Player\Security;

use App\Domain\Player\Entity\Player;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

// TODO To remove after Symfony 6.2 update. Use new login function instead
class PlayerAuthenticator
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function authenticate(Player $player): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));
    }
}
