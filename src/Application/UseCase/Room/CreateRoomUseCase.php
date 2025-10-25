<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Application\UseCase\Player\ChangeRoomUseCase;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

class CreateRoomUseCase
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly ChangeRoomUseCase $changeRoomUseCase,
    ) {
    }

    public function execute(Player $player, string $roomName, bool $isGameMastered = false): Room
    {
        $room = new Room();
        $room->setName($roomName);

        $this->changeRoomUseCase->execute($player, $room);
        $player->setRoles(['ROLE_ADMIN']);

        if ($isGameMastered) {
            $room->setIsGameMastered(true);
            $player->setRoles(['ROLE_MASTER']);
            $player->setStatus(PlayerStatus::SPECTATING);
        }

        $this->roomRepository->store($room);
        $this->persistenceAdapter->flush();

        return $room;
    }
}
