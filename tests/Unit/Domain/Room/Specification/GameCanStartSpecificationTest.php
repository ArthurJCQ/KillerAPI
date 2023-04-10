<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\Specification\AllPlayersAddedMissionSpecification;
use App\Domain\Room\Specification\EnoughMissionInRoomSpecification;
use App\Domain\Room\Specification\EnoughPlayerInRoomSpecification;
use App\Domain\Room\Specification\GameCanStartSpecification;
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

    public function testIsNotSatisfiedByPlayers(): void
    {
        $room = $this->prophesize(Room::class);

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
