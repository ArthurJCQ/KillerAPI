<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase;

use App\Entity\Mission;
use App\Entity\Player;
use App\Entity\Room;
use App\Exception\NotEnoughMissionsInRoomException;
use App\Exception\NotEnoughPlayersInRoomException;
use App\Repository\MissionRepository;
use App\UseCase\CanStartGameUseCase;
use Codeception\Stub\Expected;
use Doctrine\Common\Collections\ArrayCollection;

class CanStartGameUseCaseTest extends \Codeception\Test\Unit
{
    private CanStartGameUseCase $canStartGameUseCase;

    public function testGameCanStart(): void
    {
        $mission1 = $this->make(Mission::class);
        $mission2 = $this->make(Mission::class);
        $mission3 = $this->make(Mission::class);

        $player1 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission1])]);
        $player2 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission2])]);
        $player3 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission3])]);

        $missionRepository = $this->make(
            MissionRepository::class,
            ['getMissionsByRoomAndAuthor' => Expected::once([$mission1, $mission2])],
        );

        $room = $this->make(
            Room::class,
            ['getPlayers' => Expected::once(new ArrayCollection([$player1, $player2, $player3]))],
        );

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository);

        $this->canStartGameUseCase->execute($room);
    }

    public function testNotEnoughMissions(): void
    {
        $mission1 = $this->make(Mission::class);
        $mission2 = $this->make(Mission::class);

        $player1 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission1])]);
        $player2 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission2])]);
        $player3 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once()]);

        $missionRepository = $this->make(
            MissionRepository::class,
            ['getMissionsByRoomAndAuthor' => Expected::once([$mission1, $mission2])],
        );

        $room = $this->make(
            Room::class,
            ['getPlayers' => Expected::once(new ArrayCollection([$player1, $player2, $player3]))],
        );

        $this->expectException(NotEnoughMissionsInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository);
        $this->canStartGameUseCase->execute($room);
    }

    public function testNotEnoughPlayersAddedMissions(): void
    {
        $mission1 = $this->make(Mission::class);
        $mission2 = $this->make(Mission::class);
        $mission3 = $this->make(Mission::class);

        $player1 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission1])]);
        $player2 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission2])]);
        $player3 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::once([$mission3])]);

        $missionRepository = $this->make(
            MissionRepository::class,
            ['getMissionsByRoomAndAuthor' => Expected::once([$mission1])],
        );

        $room = $this->make(
            Room::class,
            ['getPlayers' => Expected::once(new ArrayCollection([$player1, $player2, $player3]))],
        );

        $this->expectException(NotEnoughMissionsInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository);
        $this->canStartGameUseCase->execute($room);
    }

    public function testNotEnoughPlayersInRoom(): void
    {
        $player1 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::never()]);
        $player2 = $this->make(Player::class, ['getAuthoredMissionsInRoom' => Expected::never()]);

        $room = $this->make(
            Room::class,
            ['getPlayers' => Expected::once(new ArrayCollection([$player1, $player2]))],
        );

        $missionRepository = $this->make(
            MissionRepository::class,
            ['getMissionsByRoomAndAuthor' => Expected::never()],
        );

        $this->expectException(NotEnoughPlayersInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository);
        $this->canStartGameUseCase->execute($room);
    }
}
