<?php

declare(strict_types=1);

namespace App\Domain\Mission;

class InMemoryMissionGenerator implements MissionGeneratorInterface
{
    private array $missions = [
        'Eliminate your target while wearing a red hat',
        'Complete the elimination in a public place with at least 5 witnesses',
        'Make your target laugh before eliminating them',
        'Eliminate your target using only words starting with the letter "M"',
        'Complete the mission while speaking in a foreign accent',
        'Get your target to say the word "banana" three times before elimination',
        'Eliminate your target while dancing',
        'Complete the mission without your target seeing your face',
        'Make your target sing a song before their elimination',
        'Eliminate your target using a prop (toy, book, or harmless object)',
        'Complete the mission while holding a cup of coffee',
        'Get your target to give you a high-five before elimination',
        'Eliminate your target after they complete a riddle',
        'Complete the mission while wearing sunglasses indoors',
        'Make your target draw a picture before elimination',
        'Eliminate your target while reciting a poem',
        'Complete the mission after getting your target to tell you a joke',
        'Eliminate your target in less than 60 seconds of conversation',
        'Complete the mission while standing on one foot',
        'Get your target to teach you something before elimination',
    ];

    public function generate(): string
    {
        return $this->missions[array_rand($this->missions)];
    }
}
