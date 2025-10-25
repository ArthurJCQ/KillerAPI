# Quick Start: AI-Powered Game Creation

## 🚀 Get Started in 3 Steps

### Step 1: Configure API Key

Add your OpenRouter API key to `.env`:

```bash
OPENROUTER_API_KEY=sk-or-v1-your-api-key-here
```

> Get your API key at: https://openrouter.ai/

### Step 2: Add Controller Endpoint

Create or update a controller:

```php
<?php

namespace App\Api\Controller;

use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;
use App\Domain\Player\Entity\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AiGameController extends AbstractController
{
    #[Route('/api/ai-game', methods: ['POST'])]
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
                'room' => [
                    'id' => $room->getId(),
                    'name' => $room->getName(),
                    'missionsCount' => $room->getMissions()->count(),
                    'isGameMastered' => $room->isGameMastered(),
                ],
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

### Step 3: Test It!

```bash
# Create a game with default settings (10 missions)
curl -X POST http://localhost:8000/api/ai-game \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"roomName": "My AI Game"}'

# Create a spy-themed game with 15 missions
curl -X POST http://localhost:8000/api/ai-game \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "roomName": "Secret Agent Party",
    "missionsCount": 15,
    "theme": "spy"
  }'
```

## 📝 Request/Response Examples

### Request 1: Basic Game
```json
{
  "roomName": "Epic Party"
}
```

### Response 1:
```json
{
  "success": true,
  "room": {
    "id": "ABC12",
    "name": "Epic Party",
    "missionsCount": 10,
    "isGameMastered": true
  }
}
```

### Request 2: Themed Game
```json
{
  "roomName": "Medieval Quest",
  "missionsCount": 12,
  "theme": "medieval"
}
```

### Response 2:
```json
{
  "success": true,
  "room": {
    "id": "XYZ89",
    "name": "Medieval Quest",
    "missionsCount": 12,
    "isGameMastered": true
  }
}
```

## 🎨 Available Themes

| Theme | Description | Example Mission |
|-------|-------------|----------------|
| `spy` | Secret agent operations | "Plant a listening device near your target" |
| `medieval` | Knights and castles | "Challenge your target to a duel" |
| `office` | Corporate environment | "Steal your target's coffee mug" |
| `pirates` | High seas adventure | "Make your target walk the plank" |
| `superhero` | Comic book style | "Save your target from a villain" |
| `zombie` | Zombie apocalypse | "Survive a zombie attack together" |
| `detective` | Murder mystery | "Find evidence against your target" |
| `space` | Sci-fi missions | "Repair the spaceship with your target" |

## 🛠️ Integration in Services

Use the use case in any service:

```php
class MyGameService
{
    public function __construct(
        private readonly CreateGameMasteredRoomWithAiMissionsUseCase $createGameUseCase,
    ) {}

    public function createQuickGame(Player $player): string
    {
        $room = $this->createGameUseCase->execute(
            roomName: "{$player->getName()}'s Game",
            gameMaster: $player,
        );

        return $room->getId();
    }
}
```

## ✅ Verify Installation

Check if services are registered:

```bash
# Check the AI generator
php bin/console debug:container MissionGeneratorInterface

# Check the use case
php bin/console debug:container CreateGameMasteredRoomWithAiMissionsUseCase
```

## 🧪 Run Tests

```bash
./vendor/bin/phpunit tests/Unit/Infrastructure/Ai/OpenRouterMissionGeneratorTest.php
```

## 📚 More Documentation

- **Quick Reference:** This file
- **Detailed Guide:** `README.AI-GAME-CREATION.md`
- **Examples:** `docs/ai-game-creation-example.md`
- **Architecture:** `docs/ai-architecture-diagram.txt`
- **Implementation:** `IMPLEMENTATION-SUMMARY.md`

## 🐛 Troubleshooting

### Error: "OPENROUTER_API_KEY not set"
- Check your `.env` file
- Restart your PHP/Symfony server
- Run: `php bin/console debug:container --env-vars`

### Error: "Failed to generate missions with AI"
- Check your API key is valid
- Verify internet connection
- Check OpenRouter API status
- Review logs: `var/log/dev.log`

### No missions generated
- Check mission count > 0
- Verify theme is a valid string
- Check API response in logs

## 💡 Pro Tips

1. **Cache Results**: Store generated missions for reuse
2. **Fallback**: Have default missions if AI fails
3. **Rate Limiting**: Implement rate limits for AI calls
4. **Monitoring**: Track AI generation success rates
5. **Themes**: Create a theme picker UI for better UX

## 🎯 Next Steps

1. Add the controller endpoint to your API
2. Test with Postman or curl
3. Add frontend UI for theme selection
4. Monitor AI generation metrics
5. Customize prompts for better missions

---

**Status:** ✅ Ready to use!

For questions or issues, see the detailed documentation in `README.AI-GAME-CREATION.md`
