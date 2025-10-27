<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\DeathConfirmationNotification;
use App\Domain\Notifications\KillerNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Player\Entity\Player;
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
    ) {
    }

    /**
     * Execute the player killed use case.
     *
     * @param Player $player The player who was killed
     * @param KillerNotification|null $killerNotification Optional notification to send to the killer (default: DeathConfirmationNotification)
     * @param KillerNotification|null $killersKillerNotification Optional notification to send to the killer's killer
     * @param bool $awardPoints Whether to award points to the killer (default: true)
     */
    public function execute(
        Player $player,
        ?KillerNotification $killerNotification = null,
        ?KillerNotification $killersKillerNotification = null,
        bool $awardPoints = true,
    ): void {
        $killer = $player->getKiller();
        $target = $player->getTarget();
        $assignedMission = $player->getAssignedMission();

        if ($killer === null || $target === null) {
            return;
        }

        $player->setTarget(null);
        $player->setAssignedMission(null);

        $this->persistenceAdapter->flush();

        $killer->setTarget($target);
        $killer->setAssignedMission($assignedMission);
        $killer->setMissionSwitchUsed(false);

        if ($awardPoints) {
            $killer->addPoints(10);
        }

        // Notify the killer (default to DeathConfirmationNotification)
        $notificationToSend = $killerNotification ?? DeathConfirmationNotification::to($killer);
        $this->killerNotifier->notify($notificationToSend);

        // If there's a notification for the killer's killer, send it
        if ($killersKillerNotification !== null) {
            $this->killerNotifier->notify($killersKillerNotification);
        }
    }
}
