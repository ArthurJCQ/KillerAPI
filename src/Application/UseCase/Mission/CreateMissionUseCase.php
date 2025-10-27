<?php

declare(strict_types=1);

namespace App\Application\UseCase\Mission;

use App\Application\Dto\NewMissionDto;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CreateMissionUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly MissionRepository $missionRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    public function execute(NewMissionDto $dto): Mission
    {
        $mission = new Mission();
        $mission->setContent($dto->content);
        $mission->setAuthor($dto->author);
        $mission->setRoom($dto->room);
        $mission->setIsSecondaryMission($dto->isSecondaryMission);

        $this->missionRepository->store($mission);

        $this->logger?->info('Mission created', [
            'mission_id' => $mission->getId(),
            'author_id' => $dto->author?->getId(),
            'room_id' => $dto->room?->getId(),
            'is_secondary' => $dto->isSecondaryMission,
        ]);

        return $mission;
    }

    public function executeAndFlush(NewMissionDto $dto): Mission
    {
        $mission = $this->execute($dto);
        $this->persistenceAdapter->flush();

        return $mission;
    }
}
