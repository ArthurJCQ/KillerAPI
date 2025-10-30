<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\DeathConfirmationNotification;
use App\Domain\Notifications\KillerNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Player\PlayerUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PlayerKilledUseCase implements PlayerUseCase, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly KillerNotifier $killerNotifier,
        private readonly PlayerRepository $playerRepository,
    ) {
    }

    /**
     * Execute the player killed use case.
     *
     * @param Player $player The player who was killed
     * @param KillerNotification|null $killerNotification Optional notification to send to the killer
     * @param bool $awardPoints Whether to award points to the killer (default: true)
     */
    public function execute(
        Player $player,
        ?KillerNotification $killerNotification = null,
        bool $awardPoints = true,
    ): void {
        $killer = $this->playerRepository->findKiller($player);
        $target = $player->getTarget();
        $assignedMission = $player->getAssignedMission();

        if ($killer === null || $target === null) {
            return;
        }

        // Clear all relationships on the killed player first
        $player->setTarget(null);
        $player->setAssignedMission(null);

        // Now reassign the killer's target to the killed player's target
        $killer->setTarget($target);
        $killer->setAssignedMission($assignedMission);
        $killer->setMissionSwitchUsed(false);

        if ($awardPoints) {
            $killer->addPoints(10);
        }

        $notificationToSend = $killerNotification ?? DeathConfirmationNotification::to($killer);
        $this->killerNotifier->notify($notificationToSend);
    }
}
