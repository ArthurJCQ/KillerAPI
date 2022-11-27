<?php

declare(strict_types=1);

namespace App\Domain\Mission\Security;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MissionVoter extends Voter
{
    public const EDIT_MISSION = 'edit_room';
    public const VIEW_MISSION = 'view_room';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT_MISSION, self::VIEW_MISSION], true)) {
            return false;
        }

        return $subject instanceof Mission;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $player = $token->getUser();

        if (!$player instanceof Player) {
            return false;
        }

        /** @var Mission $mission */
        $mission = $subject;

        return match ($attribute) {
            self::VIEW_MISSION => $this->canView($mission, $player),
            self::EDIT_MISSION => $this->canEdit($mission, $player),
            default => throw new \LogicException('This code should not be reached'),
        };
    }

    private function canView(Mission $mission, Player $player): bool
    {
        return $mission->getAuthor() === $player || $player->getAssignedMission() === $mission;
    }

    private function canEdit(Mission $mission, Player $player): bool
    {
        return $mission->getAuthor() === $player;
    }
}
