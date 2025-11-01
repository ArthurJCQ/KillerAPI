<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ContestKillUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly PlayerRepository $playerRepository,
    ) {
        $this->logger = new NullLogger();
    }

    public function execute(Player $player): void
    {
        // Validation: Player must be in DYING status
        if ($player->getStatus() !== PlayerStatus::DYING) {
            throw new PlayerKilledException('PLAYER_NOT_DYING');
        }

        // Validation: Room must be in game
        $room = $player->getRoom();

        if ($room?->getStatus() !== Room::IN_GAME) {
            throw new RoomNotInGameException('ROOM_NOT_IN_GAME');
        }

        // Contest the kill - set player back to ALIVE
        $player->setStatus(PlayerStatus::ALIVE);

        $this->persistenceAdapter->flush();

        $this->logger?->info(
            'Player {player} successfully contested their death',
            ['player' => $player->getId()],
        );
    }
}
