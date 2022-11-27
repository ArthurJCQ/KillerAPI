<?php

declare(strict_types=1);

namespace App\Domain\Room\Security;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoomVoter extends Voter
{
    public const EDIT_ROOM = 'edit_room';
    public const VIEW_ROOM = 'view_room';
    public const CREATE_ROOM = 'create_room';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT_ROOM, self::VIEW_ROOM, self::CREATE_ROOM], true)) {
            return false;
        }

        return $subject instanceof Room || ($attribute === self::CREATE_ROOM && $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $player = $token->getUser();

        if (!$player instanceof Player) {
            return false;
        }

        /** @var Room $room */
        $room = $subject;

        return match ($attribute) {
            self::VIEW_ROOM => $this->canView($room, $player),
            self::EDIT_ROOM => $this->canEdit($room, $player),
            self::CREATE_ROOM => $this->canCreate($player),
            default => throw new \LogicException('This code should not be reached'),
        };
    }

    private function canView(Room $room, Player $player): bool
    {
        return in_array($player, $room->getPlayers()->toArray(), true);
    }

    private function canEdit(Room $room, Player $player): bool
    {
        return $room->getAdmin() === $player;
    }

    private function canCreate(Player $player): bool
    {
        return !$player->getRoom();
    }
}
