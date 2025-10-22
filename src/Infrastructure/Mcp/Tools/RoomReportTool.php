<?php

declare(strict_types=1);

namespace App\Infrastructure\Mcp\Tools;

use App\Application\UseCase\Room\RoomReportUseCase;
use Mcp\Capability\Attribute\McpTool;

#[McpTool(
    name: 'room_report',
    description: 'Get a comprehensive report about a room including game status, player and mission counts, ...',
)]
readonly class RoomReportTool
{
    public function __construct(private RoomReportUseCase $roomReportUseCase)
    {
    }

    /**
     * Generate a room report.
     *
     * @param string $roomId The ID or code of the room to report on
     */
    public function __invoke(string $roomId): array
    {
        return $this->roomReportUseCase->execute($roomId);
    }
}
