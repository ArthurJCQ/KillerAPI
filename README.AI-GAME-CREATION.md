# AI-Powered Game Creation Feature

## Overview

This feature allows automatic creation of game rooms with AI-generated missions using OpenRouter's AI services (powered by Claude 3.5 Sonnet by default).

## Quick Start

### 1. Configuration

Add your OpenRouter API key to `.env`:

```env
OPENROUTER_API_KEY=sk-or-v1-your-api-key-here
```

Get your API key from: https://openrouter.ai/

### 2. Usage

Inject the use case into your controller or service:

```php
use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;

public function __construct(
    private readonly GenerateRoomWithMissionUseCase $createGameUseCase,
) {
}

// Create a game with AI missions
$room = $this->createGameUseCase->execute(
    roomName: 'Epic Party Game',
    gameMaster: $player,
    missionsCount: 10,        // optional, default: 10
    theme: 'spy',            // optional, default: null
);

// Returns the created Room entity with missions
echo "Room created: " . $room->getId();
```

## Architecture

### Clean Architecture Implementation

```
┌─────────────────────────────────────────────────────┐
│ Domain Layer (Business Rules)                        │
│  └── MissionGeneratorInterface                      │
│       (defines contract for mission generation)     │
└─────────────────────────────────────────────────────┘
                        ▲
                        │ implements
                        │
┌─────────────────────────────────────────────────────┐
│ Infrastructure Layer (External Services)            │
│  └── OpenRouterMissionGenerator                     │
│       (AI implementation via OpenRouter API)        │
└─────────────────────────────────────────────────────┘
                        ▲
                        │ uses
                        │
┌─────────────────────────────────────────────────────┐
│ Application Layer (Use Cases)                       │
│  └── CreateGameMasteredRoomWithAiMissionsUseCase   │
│       (orchestrates room + mission creation)        │
└─────────────────────────────────────────────────────┘
```

### Files

**Domain Layer:**
- `src/Domain/Mission/MissionGeneratorInterface.php` - Interface definition

**Application Layer:**
- `src/Application/UseCase/Room/CreateGameMasteredRoomWithAiMissionsUseCase.php` - Use case

**Infrastructure Layer:**
- `src/Infrastructure/Ai/OpenRouterMissionGenerator.php` - AI implementation

**Configuration:**
- `config/services.yaml` - Service definitions

**Tests:**
- `tests/Unit/Infrastructure/Ai/OpenRouterMissionGeneratorTest.php` - Unit tests

**Documentation:**
- `docs/ai-game-creation-example.md` - Detailed examples and usage

## Features

✅ **AI-Generated Missions**: Creative, engaging missions powered by Claude 3.5 Sonnet
✅ **Themed Games**: Support for custom themes (spy, medieval, office, pirates, etc.)
✅ **Clean Architecture**: Follows domain-driven design principles
✅ **Easy Testing**: Interface-based design allows easy mocking
✅ **Error Handling**: Graceful error handling with detailed logging
✅ **Configurable**: Customizable mission count and themes

## Example API Endpoint

```php
#[Route('/api/game/create-ai', methods: ['POST'])]
public function createAiGame(
    CreateGameMasteredRoomWithAiMissionsUseCase $useCase,
    #[CurrentUser] Player $currentPlayer,
    Request $request,
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    try {
        $room = $useCase->execute(
            roomName: $data['roomName'] ?? 'AI Game',
            gameMaster: $currentPlayer,
            missionsCount: $data['missionsCount'] ?? 10,
            theme: $data['theme'] ?? null,
        );

        return new JsonResponse([
            'success' => true,
            'roomId' => $room->getId(),
        ], Response::HTTP_CREATED);
    } catch (\RuntimeException $e) {
        return new JsonResponse([
            'success' => false,
            'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
```

## Popular Themes

- `spy` - Secret agent missions
- `medieval` - Knights and castles
- `office` - Corporate environment
- `pirates` - High seas adventure
- `superhero` - Comic book style
- `zombie` - Zombie apocalypse
- `casino` - Vegas-style
- `western` - Wild west
- `space` - Sci-fi missions
- `detective` - Murder mystery

## Testing

Run the tests:

```bash
./vendor/bin/phpunit tests/Unit/Infrastructure/Ai/OpenRouterMissionGeneratorTest.php
```

## Implementation Details

The use case follows these steps:

1. **Create Room**: Creates a game-mastered room with the player as admin
2. **Generate Missions**: Calls AI to generate creative missions
3. **Create Mission Entities**: Converts AI responses into Mission entities
4. **Associate**: Links missions to room and game master
5. **Persist**: Saves everything to the database

## Error Handling

The implementation handles various error scenarios:

- API connection failures
- Invalid API responses
- Database errors
- Invalid parameters

All errors are logged and wrapped in `\RuntimeException` with clear messages.

## Dependencies

- `symfony/http-client` - For API requests
- `psr/log` - For logging
- OpenRouter API key - For AI generation

## Future Enhancements

Possible improvements:

- [ ] Mission validation rules
- [ ] Caching for generated missions
- [ ] Rate limiting for API calls
- [ ] Fallback generator if AI unavailable
- [ ] Support for multiple AI models
- [ ] Mission difficulty levels
- [ ] Custom prompts per theme

## Support

For detailed examples, see: `docs/ai-game-creation-example.md`

For issues or questions, please create an issue in the repository.
