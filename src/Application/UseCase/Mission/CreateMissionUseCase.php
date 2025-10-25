<?php

declare(strict_types=1);

namespace App\Application\UseCase\Mission;

use App\Api\Exception\KillerBadRequestHttpException;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;

readonly class CreateMissionUseCase
{
    public function __construct(
        private MissionRepository $missionRepository,
        private PersistenceAdapterInterface $persistenceAdapter,
    ) {
    }

    public function execute(string $content, Player $author): Mission
    {
        $room = $author->getRoom();

        if (!$room || $room->getStatus() !== Room::PENDING) {
            throw new KillerBadRequestHttpException('CAN_NOT_ADD_MISSIONS');
        }

        $mission = new Mission();
        $mission->setContent($content);

        $author->addAuthoredMission($mission);

        $this->missionRepository->store($mission);
        $this->persistenceAdapter->flush();

        return $mission;
    }
}
