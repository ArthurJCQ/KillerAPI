<?php

declare(strict_types=1);

namespace App\Infrastructure\Mcp\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(
    name: 'room_report',
    description: 'Generate a comprehensive and nicely formatted report about a game room',
)]
readonly class ReportPrompt
{
    /**
     * Generate a prompt to build a room report.
     *
     * @param string $roomId The ID or code of the room to report on
     */
    public function __invoke(string $roomId): array
    {
        return [
            'role' => 'user',
            // phpcs:ignore Squiz.Arrays.ArrayDeclaration.NoComma
            'content' => <<<PROMPT
You are tasked with generating a comprehensive report about a game room in the Killer game application.

**Instructions:**

1. Use the `room_report` tool with the room ID: {$roomId}
2. The tool will return the following information:
   - Room ID and name
   - Game status (IN_GAME, PENDING, or ENDED)
   - Total number of players
   - Number of alive players
   - Total number of missions
   - Creation date and end date
   - Whether the game is game-mastered
   - Winner information (if any)

3. Present the information in a clear, well-formatted manner following this structure:

**Room Report Format:**

```
# Game Room Report: [Room Name]

## General Information
- **Room ID:** [ID]
- **Room Name:** [Name]
- **Created:** [Date]
- **End Date:** [Date]
- **Game Master Mode:** [Yes/No]

## Game Status
- **Status:** [IN_GAME/PENDING/ENDED]
- **Is Currently Playing:** [Yes/No]

## Players Overview
- **Total Players:** [X]
- **Alive Players:** [X]
- **Eliminated Players:** [X]

## Missions
- **Total Missions:** [X]

## Game Outcome
- **Winner:** [Player name or "No winner yet"]
```

4. Add contextual insights such as:
   - If the game is IN_GAME, mention the current state of competition
   - If many players have been eliminated, note the intensity of the game
   - If the game hasn't started (PENDING), mention it's waiting to begin
   - If the game has ENDED, highlight the winner

5. Use clear formatting with headers, bullet points, and emphasis where appropriate

**Now generate the report for room: {$roomId}**
PROMPT,
        ];
    }
}
