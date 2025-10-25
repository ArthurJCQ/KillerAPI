# AI-Powered Game Creation - Usage Examples

This document demonstrates how to use the AI-powered game creation feature in KillerAPI.

## Architecture Overview

The implementation follows clean architecture principles:

```
Domain Layer (Interfaces)
    └── MissionGeneratorInterface
         ↑
         │ implements
         │
Infrastructure Layer (AI Implementation)
    └── OpenRouterMissionGenerator
         ↑
         │ depends on
         │
Application Layer (Use Cases)
    └── CreateGameMasteredRoomWithAiMissionsUseCase
```

## Components

### 1. Domain Interface: `MissionGeneratorInterface`

Located in `src/Domain/Mission/MissionGeneratorInterface.php`

Defines the contract for mission generation:

```php
interface MissionGeneratorInterface
{
    public function generateMissions(int $count, ?string $theme = null): array;
}
```

### 2. Infrastructure Implementation: `OpenRouterMissionGenerator`

Located in `src/Infrastructure/Ai/OpenRouterMissionGenerator.php`

Implements the interface using OpenRouter API (with Claude 3.5 Sonnet by default):
- Generates creative missions using AI
- Supports themed missions
- Handles API errors gracefully

### 3. Application Use Case: `CreateGameMasteredRoomWithAiMissionsUseCase`

Located in `src/Application/UseCase/Room/CreateGameMasteredRoomWithAiMissionsUseCase.php`

Orchestrates the entire process:
1. Creates a game-mastered room
2. Generates missions using the AI generator
3. Associates missions with the room
4. Persists everything to the database

## Configuration

### 1. Environment Variables

Add your OpenRouter API key to `.env`:

```env
OPENROUTER_API_KEY=sk-or-v1-your-api-key-here
```

### 2. Service Configuration

The service is configured in `config/services.yaml`:

```yaml
App\Domain\Mission\MissionGeneratorInterface:
    class: App\Infrastructure\Ai\OpenRouterMissionGenerator
    arguments:
        $openRouterApiKey: '%env(OPENROUTER_API_KEY)%'
```

## Usage Examples

### Example 1: Basic Usage in a Controller

```php
use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class GameController extends AbstractController
{
    #[Route('/api/game/create-ai', methods: ['POST'])]
    public function createAiGame(
        GenerateRoomWithMissionUseCase $useCase,
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
                'roomName' => $room->getName(),
                'missionsCount' => $room->getMissions()->count(),
            ], Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
```

### Example 2: Service Usage

```php
use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;
use App\Domain\Player\Entity\Player;

class GameService
{
    public function __construct(
        private readonly GenerateRoomWithMissionUseCase $createGameUseCase,
    ) {
    }

    public function createBasicGame(Player $gameMaster): string
    {
        // Creates a room with 10 AI-generated missions
        $room = $this->createGameUseCase->execute(
            roomName: 'Epic AI Game',
            gameMaster: $gameMaster,
        );

        return $room->getId();
    }

    public function createThemedGame(Player $gameMaster, string $theme): string
    {
        // Creates a room with 15 themed missions
        $room = $this->createGameUseCase->execute(
            roomName: ucfirst($theme) . ' Party',
            gameMaster: $gameMaster,
            missionsCount: 15,
            theme: $theme,
        );

        return $room->getId();
    }
}
```

### Example 3: Available Themes

```php
// Spy-themed missions
$room = $useCase->execute(
    roomName: 'Secret Agent Party',
    gameMaster: $player,
    missionsCount: 12,
    theme: 'spy',
);

// Medieval-themed missions
$room = $useCase->execute(
    roomName: 'Knights and Dragons',
    gameMaster: $player,
    missionsCount: 10,
    theme: 'medieval',
);

// Office-themed missions
$room = $useCase->execute(
    roomName: 'Corporate Assassin',
    gameMaster: $player,
    missionsCount: 8,
    theme: 'office',
);

// Custom theme
$room = $useCase->execute(
    roomName: 'Pirate Adventure',
    gameMaster: $player,
    missionsCount: 10,
    theme: 'pirates on the high seas',
);
```

### Example 4: Error Handling

```php
try {
    $room = $useCase->execute(
        roomName: 'Safe Game',
        gameMaster: $player,
        missionsCount: 10,
    );

    // Success - room created with missions
    echo "Room {$room->getId()} created with {$room->getMissions()->count()} missions";

} catch (\RuntimeException $e) {
    // Handle error - log it, notify user, etc.
    $logger->error('Failed to create AI game', [
        'error' => $e->getMessage(),
        'player_id' => $player->getId(),
    ]);

    // Fallback: create room without AI missions
    // or retry with different parameters
}
```

### Example 5: Testing with Mock Generator

For testing, you can create a mock implementation:

```php
// tests/Fixtures/MockMissionGenerator.php
class MockMissionGenerator implements MissionGeneratorInterface
{
    public function generateMissions(int $count, ?string $theme = null): array
    {
        $missions = [];
        for ($i = 1; $i <= $count; $i++) {
            $missions[] = "Test mission {$i}" . ($theme ? " - {$theme} themed" : "");
        }
        return $missions;
    }
}

// In your test configuration (config/services_test.yaml)
when@test:
    services:
        App\Domain\Mission\MissionGeneratorInterface:
            class: App\Tests\Fixtures\MockMissionGenerator
```

## API Request Examples

### cURL Example

```bash
curl -X POST http://localhost:8000/api/game/create-ai \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "roomName": "Spy Mission Party",
    "missionsCount": 12,
    "theme": "spy"
  }'
```

### Response

```json
{
  "success": true,
  "roomId": "ABC12",
  "roomName": "Spy Mission Party",
  "missionsCount": 12
}
```

## Popular Themes

- **spy** - Secret agent, espionage missions
- **medieval** - Knights, castles, dragons
- **office** - Corporate environment missions
- **pirates** - Sailing, treasure hunting
- **superhero** - Comic book style missions
- **zombie** - Zombie apocalypse survival
- **casino** - Vegas-style missions
- **western** - Wild west, cowboys
- **space** - Sci-fi, astronaut missions
- **detective** - Murder mystery, investigation

## Benefits of This Architecture

1. **Separation of Concerns**: Domain logic is separated from infrastructure details
2. **Testability**: Easy to mock the MissionGeneratorInterface for testing
3. **Flexibility**: Can swap AI providers without changing application logic
4. **Maintainability**: Clear boundaries between layers
5. **Extensibility**: Easy to add new mission generators or use cases

## Next Steps

- Add mission validation rules in the domain layer
- Create additional themed templates
- Implement caching for generated missions
- Add rate limiting for AI API calls
- Create a fallback generator if AI service is unavailable
