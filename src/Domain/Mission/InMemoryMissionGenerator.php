<?php

declare(strict_types=1);

namespace App\Domain\Mission;

use App\Domain\Mission\Enum\MissionTheme;

class InMemoryMissionGenerator implements MissionGeneratorInterface
{
    private array $missionsByTheme = [
        MissionTheme::STEALTH->value => [
            'Complete the mission without your target seeing your face',
            'Approach your target from behind without being noticed',
            'Eliminate your target while they are distracted',
            'Complete the mission in complete silence',
        ],
        MissionTheme::SOCIAL->value => [
            'Make your target laugh before eliminating them',
            'Get your target to say the word "banana" three times before elimination',
            'Get your target to give you a high-five before elimination',
            'Complete the mission after getting your target to tell you a joke',
            'Get your target to teach you something before elimination',
            'Complete the elimination in a public place with at least 5 witnesses',
            'Make friends with your target before elimination',
            'Get your target to share a secret with you',
        ],
        MissionTheme::PERFORMANCE->value => [
            'Complete the mission while speaking in a foreign accent',
            'Eliminate your target while dancing',
            'Make your target sing a song before their elimination',
            'Eliminate your target while reciting a poem',
            'Complete the mission while pretending to be someone else',
            'Eliminate your target while doing an impression',
        ],
        MissionTheme::CHALLENGE->value => [
            'Eliminate your target using only words starting with the letter "M"',
            'Complete the mission while holding a cup of coffee',
            'Eliminate your target after they complete a riddle',
            'Complete the mission while wearing sunglasses indoors',
            'Eliminate your target in less than 60 seconds of conversation',
            'Complete the mission while standing on one foot',
            'Eliminate your target while walking backwards',
            'Complete the mission without using the letter "E" in any word',
        ],
        MissionTheme::CREATIVE->value => [
            'Eliminate your target while wearing a red hat',
            'Eliminate your target using a prop (toy, book, or harmless object)',
            'Make your target draw a picture before elimination',
            'Complete the mission while wearing something unusual',
            'Eliminate your target after creating something together',
            'Complete the mission while showing your target a magic trick',
        ],
    ];

    public function generateMissions(int $count, ?MissionTheme $theme = null): array
    {
        $availableMissions = $theme !== null
            ? $this->missionsByTheme[$theme->value]
            : array_merge(...array_values($this->missionsByTheme));

        // If count is greater than available missions, allow duplicates
        if ($count >= \count($availableMissions)) {
            $missions = $availableMissions;
            $remaining = $count - \count($availableMissions);

            // Add random missions to reach the count
            for ($i = 0; $i < $remaining; ++$i) {
                $missions[] = $availableMissions[array_rand($availableMissions)];
            }

            return $missions;
        }

        // Randomly select $count missions without duplicates
        $shuffled = $availableMissions;
        shuffle($shuffled);

        return \array_slice($shuffled, 0, $count);
    }
}
