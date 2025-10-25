<?php

declare(strict_types=1);

namespace App\Infrastructure\Ai\Agent;

use App\Domain\Mission\MissionGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Bridge\Albert\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class KillerMissionsAgent implements MissionGeneratorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire(env: 'OPENROUTER_API_KEY')]
        private string $openRouterApiKey,
        #[Autowire(env: 'OPENROUTER_MODEL')]
        private string $openRouterModel,
        #[Autowire(env: 'OPENROUTER_URL')]
        private string $openRouterUrl,
    ) {
    }

    public function generateMissions(int $count, ?string $theme = null): array
    {
        $this->logger->info('Generating missions with OpenRouter AI', [
            'count' => $count,
            'theme' => $theme,
        ]);

        $prompt = $this->buildMissionGenerationPrompt($count, $theme);

        try {
            // Create AI Platform instance
            $platform = PlatformFactory::create(
                apiKey: $this->openRouterApiKey,
                baseUrl: $this->openRouterUrl,
            );

            // Ensure model is non-empty
            if ($this->openRouterModel === '') {
                throw new \RuntimeException('OpenRouter model cannot be empty');
            }

            // Create Agent
            $agent = new Agent($platform, $this->openRouterModel);

            // Create message bag with system and user messages
            $messages = new MessageBag(
                Message::forSystem('You are a creative game designer for "Killer Party",'
                    . 'an assassination game where players are secretly assigned targets and missions.'),
                Message::ofUser($prompt),
            );

            // Call the agent
            $result = $agent->call($messages);

            $content = $result->getContent();

            if (!$content || !\is_string($content)) {
                throw new \RuntimeException('No valid content in OpenRouter API response');
            }

            $this->logger->debug('OpenRouter API response received', [
                'model' => $this->openRouterModel,
            ]);

            // Parse the missions from the response
            $missions = $this->parseMissionsFromResponse($content);

            if (\count($missions) < $count) {
                $this->logger->warning('Received fewer missions than requested', [
                    'requested' => $count,
                    'received' => \count($missions),
                ]);
            }

            $this->logger->info('Missions generated successfully', [
                'count' => \count($missions),
            ]);

            return \array_slice($missions, 0, $count);
        } catch (\Throwable $e) {
            $this->logger->error('OpenRouter API call failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Failed to generate missions with AI: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    private function buildMissionGenerationPrompt(int $count, ?string $theme): string
    {
        $basePrompt = <<<PROMPT
Generate {$count} fun, creative, and challenging missions for the game. Each mission should:
- Be between 10-100 words
- Be appropriate for a party game (nothing dangerous or inappropriate)
- Be achievable in a social setting
- Be entertaining and engaging
- Range from easy to challenging
- Encourage social interaction and stealth

PROMPT;

        if ($theme !== null) {
            $basePrompt .= sprintf("\nTheme: %s. All missions should fit this theme.\n", $theme);
        }

        $basePrompt .= <<<PROMPT


Examples of good missions:
- "Take a selfie with your target without them knowing and show it to another player"
- "Get your target to say a specific word three times in conversation"
- "Convince your target to perform an embarrassing dance move"
- "Steal an object from your target's pocket or bag without being noticed"

Please provide exactly {$count} missions, each on a new line, numbered from 1 to {$count}.
Format: Just the numbered list, no additional commentary.
PROMPT;

        return $basePrompt;
    }

    /**
     * @return array<string> Array of mission contents
     */
    private function parseMissionsFromResponse(string $response): array
    {
        // Split by newlines and filter out empty lines
        $lines = array_filter(
            array_map('trim', explode("\n", $response)),
            static fn (string $line) => $line !== '',
        );

        $missions = [];

        foreach ($lines as $line) {
            // Remove numbering patterns like "1.", "1)", "1 -", etc.
            $cleanedLine = preg_replace('/^\d+[\.\)\-\:]\s*/', '', $line);
            if ($cleanedLine === null) {
                continue;
            }

            // Remove quotes if the entire mission is quoted
            $cleanedLine = trim($cleanedLine, '"\'');

            if ($cleanedLine === '' || \strlen($cleanedLine) < 5) {
                continue;
            }

            $missions[] = $cleanedLine;
        }

        return $missions;
    }
}
