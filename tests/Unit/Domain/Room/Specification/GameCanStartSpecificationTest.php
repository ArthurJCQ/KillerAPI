<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\Specification;

use App\Domain\Room\Entity\Room;
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
    private GameCanStartSpecification $gameCanStartSpecification;

    protected function setUp(): void
    {
        $this->enoughMissionInRoomSpecification = $this->prophesize(EnoughMissionInRoomSpecification::class);
        $this->enoughPlayerInRoomSpecification = $this->prophesize(EnoughPlayerInRoomSpecification::class);

        $this->gameCanStartSpecification = new GameCanStartSpecification(
            $this->enoughPlayerInRoomSpecification->reveal(),
            $this->enoughMissionInRoomSpecification->reveal(),
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

        $this->assertFalse($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }

    public function testIsNotSatisfiedByMissions(): void
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

        $this->assertFalse($this->gameCanStartSpecification->isSatisfiedBy($room->reveal()));
    }
}
