<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\DeathConfirmationNotification;
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

    public function execute(Player $player): void
    {
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

        $this->killerNotifier->notify(DeathConfirmationNotification::to($killer));
    }
}
