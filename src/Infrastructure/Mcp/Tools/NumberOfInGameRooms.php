<?php

declare(strict_types=1);

namespace App\Infrastructure\Mcp\Tools;

use App\Domain\Room\RoomRepository;
use Mcp\Capability\Attribute\McpTool;

#[McpTool(
    name: 'number_of_in_game_rooms',
    description: 'Number of currently playing Rooms, with IN_GAME status',
)]
readonly class NumberOfInGameRooms
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    public function __invoke(): int
    {
        return $this->roomRepository->countInGameRooms();
    }
}
