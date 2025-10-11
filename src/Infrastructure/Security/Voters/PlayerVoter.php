<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voters;

use App\Domain\Player\Entity\Player;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PlayerVoter extends Voter
{
    public const string EDIT_PLAYER = 'edit_player';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT_PLAYER && $subject instanceof Player;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $playerInSession = $token->getUser();

        if (!$playerInSession instanceof Player) {
            return false;
        }

        /** @var Player $player */
        $player = $subject;

        if ($attribute !== self::EDIT_PLAYER) {
            throw new \LogicException('This code should not be reached');
        }

        return $this->canEdit($player, $playerInSession);
    }

    private function canEdit(Player $player, Player $playerInSession): bool
    {
        return $player === $playerInSession
            || ($player->getRoom() === $playerInSession->getRoom()
                && $player->getRoom()?->getAdmin() === $playerInSession);
    }
}
