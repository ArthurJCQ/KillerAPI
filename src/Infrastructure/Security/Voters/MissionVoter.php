<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voters;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MissionVoter extends Voter
{
    public const EDIT_MISSION = 'edit_mission';
    public const VIEW_MISSION = 'view_mission';
    public const CREATE_MISSION = 'create_mission';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT_MISSION, self::VIEW_MISSION, self::CREATE_MISSION], true)) {
            return false;
        }

        return $subject instanceof Mission || ($attribute === self::CREATE_MISSION && $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $player = $token->getUser();

        if (!$player instanceof Player) {
            return false;
        }

        return match ($attribute) {
            self::VIEW_MISSION => $this->canView($subject, $player),
            self::EDIT_MISSION => $this->canEdit($subject, $player),
            self::CREATE_MISSION => $this->canPost($player),
            default => throw new \LogicException('This code should not be reached'),
        };
    }

    private function canView(mixed $mission, Player $player): bool
    {
        return $mission instanceof Mission
            && ($mission->getAuthor() === $player || $player->getAssignedMission() === $mission);
    }

    private function canEdit(mixed $mission, Player $player): bool
    {
        return $mission instanceof Mission && $mission->getAuthor() === $player;
    }

    private function canPost(Player $player): bool
    {
        $room = $player->getRoom();

        if (!$room?->isGameMastered()) {
            return true;
        }

        return $this->security->isGranted('ROLE_MASTER', $player);
    }
}
