<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voters;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoomVoter extends Voter
{
    public const string EDIT_ROOM = 'edit_room';
    public const string VIEW_ROOM = 'view_room';
    public const string CREATE_ROOM = 'create_room';

    public function __construct(
        private readonly PlayerRepository $playerRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT_ROOM, self::VIEW_ROOM, self::CREATE_ROOM], true)) {
            return false;
        }

        return $subject instanceof Room || ($attribute === self::CREATE_ROOM && $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($attribute === self::EDIT_ROOM && $token->getUserIdentifier() === '') {
            return true;
        }

        if (!$user instanceof User) {
            return false;
        }

        $currentPlayer = $this->playerRepository->getCurrentUserPlayer($user);

        return match ($attribute) {
            self::VIEW_ROOM => $currentPlayer && $this->canView($subject, $currentPlayer),
            self::EDIT_ROOM => $currentPlayer && $this->canEdit($subject, $currentPlayer),
            self::CREATE_ROOM => !$currentPlayer && $this->canCreate($currentPlayer),
            default => throw new \LogicException('This code should not be reached'),
        };
    }

    private function canView(mixed $room, Player $player): bool
    {
        return $room instanceof Room
            && ($room->getStatus() === Room::ENDED || in_array($player, $room->getPlayers()->toArray(), true));
    }

    private function canEdit(mixed $room, Player $player): bool
    {
        return $room instanceof Room && $room->getAdmin() === $player;
    }

    private function canCreate(?Player $player = null): bool
    {
        return !$player?->getRoom();
    }
}
