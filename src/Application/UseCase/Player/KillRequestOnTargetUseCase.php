<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\KillRequestNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

readonly class KillRequestOnTargetUseCase
{
    public function __construct(
        private PersistenceAdapterInterface $persistenceAdapter,
        private KillerNotifier $killerNotifier,
    ) {
    }

    public function execute(Player $player): void
    {
        $room = $player->getRoom();
        $target = $player->getTarget();

        if ($player->getStatus() !== PlayerStatus::ALIVE) {
            throw new PlayerKilledException('PLAYER_IS_KILLED');
        }

        if ($room?->getStatus() !== Room::IN_GAME) {
            throw new RoomNotInGameException('ROOM_NOT_IN_GAME');
        }

        if ($target === null) {
            throw new PlayerHasNoKillerOrTargetException('TARGET_NOT_FOUND');
        }

        $target->setStatus(PlayerStatus::DYING);
        $this->persistenceAdapter->flush();

        $this->killerNotifier->notify(KillRequestNotification::to($target));
    }
}
