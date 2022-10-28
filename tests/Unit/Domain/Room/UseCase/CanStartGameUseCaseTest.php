<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\UseCase;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\NotEnoughMissionsInRoomException;
use App\Domain\Room\Exception\NotEnoughPlayersInRoomException;
use App\Domain\Room\UseCase\CanStartGameUseCase;
use Codeception\Stub\Expected;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CanStartGameUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private CanStartGameUseCase $canStartGameUseCase;

    public function testGameCanStart(): void
    {
        $mission1 = $this->prophesize(Mission::class);
        $mission2 = $this->prophesize(Mission::class);
        $mission3 = $this->prophesize(Mission::class);

        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal(), $player3->reveal()]));
        $room->getMissions()->shouldBeCalledOnce()->willReturn(new ArrayCollection([
            $mission1->reveal(),
            $mission2->reveal(),
            $mission3->reveal(),
        ]));

        $missionRepository = $this->prophesize(MissionRepository::class);
        $missionRepository->getMissionsByRoomAndAuthor($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn([$mission1->reveal(), $mission2->reveal()]);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository->reveal());

        $this->canStartGameUseCase->execute($room->reveal());
    }

    public function testNotEnoughMissions(): void
    {
        $mission1 = $this->prophesize(Mission::class);
        $mission2 = $this->prophesize(Mission::class);

        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal(), $player3->reveal()]));
        $room->getMissions()->shouldBeCalledOnce()->willReturn(new ArrayCollection([
            $mission1->reveal(),
            $mission2->reveal(),
        ]));

        $missionRepository = $this->prophesize(MissionRepository::class);
        $missionRepository->getMissionsByRoomAndAuthor($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn([$mission1->reveal(), $mission2->reveal()]);

        $this->expectException(NotEnoughMissionsInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository->reveal());
        $this->canStartGameUseCase->execute($room->reveal());
    }

    public function testNotEnoughPlayersAddedMissions(): void
    {
        $mission1 = $this->prophesize(Mission::class);

        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal(), $player3->reveal()]));
        $room->getMissions()->shouldNotBeCalled();

        $missionRepository = $this->prophesize(MissionRepository::class);
        $missionRepository->getMissionsByRoomAndAuthor($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn([$mission1->reveal()]);

        $this->expectException(NotEnoughMissionsInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository->reveal());
        $this->canStartGameUseCase->execute($room->reveal());
    }

    public function testNotEnoughPlayersInRoom(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal()]));
        $room->getMissions()->shouldNotBeCalled();

        $missionRepository = $this->prophesize(MissionRepository::class);
        $missionRepository->getMissionsByRoomAndAuthor(Argument::any())->shouldNotBeCalled();

        $this->expectException(NotEnoughPlayersInRoomException::class);

        $this->canStartGameUseCase = new CanStartGameUseCase($missionRepository->reveal());
        $this->canStartGameUseCase->execute($room->reveal());
    }
}
