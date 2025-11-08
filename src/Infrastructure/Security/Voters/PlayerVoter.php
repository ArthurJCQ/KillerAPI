<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voters;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PlayerVoter extends Voter
{
    public const string EDIT_PLAYER = 'edit_player';

    public function __construct(
        private readonly PlayerRepository $playerRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT_PLAYER && $subject instanceof Player;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $currentPlayer = $this->playerRepository->getCurrentUserPlayer($user);

        if ($currentPlayer === null) {
            return false;
        }

        /** @var Player $player */
        $player = $subject;

        if ($attribute !== self::EDIT_PLAYER) {
            throw new \LogicException('This code should not be reached');
        }

        return $this->canEdit($player, $currentPlayer);
    }

    private function canEdit(Player $player, Player $currentPlayer): bool
    {
        return $player === $currentPlayer
            || ($player->getRoom() === $currentPlayer->getRoom()
                && $player->getRoom()?->getAdmin() === $currentPlayer);
    }
}
