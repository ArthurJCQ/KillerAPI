<?php

declare(strict_types=1);

namespace App\Application\UseCase\Room;

use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;

final readonly class RoomReportUseCase
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    /**
     * Generate a comprehensive report for a room.
     *
     * @return array{
     *     roomId: string,
     *     roomName: string,
     *     status: string,
     *     isInGame: bool,
     *     totalPlayers: int,
     *     alivePlayers: int,
     *     totalMissions: int,
     *     createdAt: string|null,
     *     dateEnd: string|null,
     *     isGameMastered: bool,
     *     hasWinner: bool,
     *     winnerName: string|null
     * }|array{error: string, roomId: string}
     */
    public function execute(string $roomId): array
    {
        $room = $this->roomRepository->find($roomId);

        if (!$room instanceof Room) {
            return [
                'error' => 'Room not found',
                'roomId' => $roomId,
            ];
        }

        $players = $room->getPlayers();
        $alivePlayers = array_filter(
            $players->toArray(),
            static fn ($player) => $player->getStatus() === PlayerStatus::ALIVE,
        );

        return [
            'roomId' => $room->getId(),
            'roomName' => $room->getName(),
            'status' => $room->getStatus(),
            'isInGame' => $room->getStatus() === Room::IN_GAME,
            'totalPlayers' => $players->count(),
            'alivePlayers' => count($alivePlayers),
            'totalMissions' => $room->getMissions()->count(),
            'createdAt' => $room->getCreatedAt()?->format('Y-m-d H:i:s'),
            'dateEnd' => $room->getDateEnd()?->format('Y-m-d H:i:s'),
            'isGameMastered' => $room->isGameMastered(),
            'hasWinner' => $room->getWinner() !== null,
            'winnerName' => $room->getWinner()?->getName(),
        ];
    }
}
