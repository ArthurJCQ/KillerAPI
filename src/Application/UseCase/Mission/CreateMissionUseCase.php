<?php

declare(strict_types=1);

namespace App\Application\UseCase\Mission;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CreateMissionUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function execute(string $content, ?Player $author = null): Mission
    {
        $mission = new Mission();
        $mission->setContent($content);
        $mission->setAuthor($author);

        $this->logger?->info('Mission created', [
            'content' => $content,
            'author_id' => $author?->getId(),
        ]);

        return $mission;
    }
}
