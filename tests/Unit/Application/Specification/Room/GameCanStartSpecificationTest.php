<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Specification\Room;

use App\Application\Specification\Room\AllPlayersAddedMissionSpecification;
use App\Application\Specification\Room\EnoughMissionInRoomSpecification;
use App\Application\Specification\Room\EnoughPlayerInRoomSpecification;
use App\Application\Specification\Room\GameCanStartSpecification;
use App\Domain\Room\Entity\Room;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class GameCanStartSpecificationTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private ObjectProphecy $enoughPlayerInRoomSpecification;
    private ObjectProphecy $enoughMissionInRoomSpecification;
    private ObjectProphecy $allPlayersAddedMissionSpecification;
    private GameCanStartSpecification $gameCanStartSpecification;

    protected function setUp(): void
    {
        $this->enoughMissionInRoomSpecification = $this->prophesize(EnoughMissionInRoomSpecification::class);
        $this->enoughPlayerInRoomSpecification = $this->prophesize(EnoughPlayerInRoomSpecification::class);
        $this->allPlayersAddedMissionSpecification = $this->prophesize(AllPlayersAddedMissionSpecification::class);

        $this->gameCanStartSpecification = new GameCanStartSpecification(
            $this->enoughPlayerInRoomSpecification->reveal(),
            $this->enoughMissionInRoomSpecification->reveal(),
            $this->allPlayersAddedMissionSpecification->reveal(),
        );
    }

    public function testGameCanStart(): void
    {
        $room = $this->prophesize(Room::class);

        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(false);

        $this->enoughPlayerInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->enoughMissionInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->allPlayersAddedMissionSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->assertTrue($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }

    public function testIsSatisfiedForMasteredRoom(): void
    {
        $room = $this->prophesize(Room::class);

        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(true);

        $this->enoughPlayerInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->enoughMissionInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->allPlayersAddedMissionSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldNotBeCalled();

        $this->assertTrue($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }

    public function testIsNotSatisfiedByPlayers(): void
    {
        $room = $this->prophesize(Room::class);

        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(false);

        $this->enoughPlayerInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $this->enoughMissionInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldNotBeCalled();
        $this->allPlayersAddedMissionSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldNotBeCalled();

        $this->assertFalse($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }

    public function testIsNotSatisfiedByEnoughMissions(): void
    {
        $room = $this->prophesize(Room::class);

        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(false);

        $this->enoughPlayerInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->enoughMissionInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $this->allPlayersAddedMissionSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldNotBeCalled();

        $this->assertFalse($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }

    public function testIsNotSatisfiedByPlayerMissions(): void
    {
        $room = $this->prophesize(Room::class);

        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(false);

        $this->enoughPlayerInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->enoughMissionInRoomSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->allPlayersAddedMissionSpecification
            ->isSatisfiedBy($room->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->assertFalse($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }
}
