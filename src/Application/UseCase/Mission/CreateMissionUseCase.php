<?php

declare(strict_types=1);

namespace App\Application\UseCase\Mission;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class CreateMissionUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function execute(string $content, ?Player $author = null): Mission
    {
        $mission = new Mission();
        $mission->setContent($content);
        $author?->addAuthoredMission($mission);

        $this->logger?->info('Mission created', [
            'content' => $content,
            'author_id' => $author?->getId(),
        ]);

        return $mission;
    }
}
