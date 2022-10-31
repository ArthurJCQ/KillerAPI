<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\Specification;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\UseCase\DispatchMissionsUseCase;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DispatchMissionsUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private const DISPATCH_ITERATION = 100;

    private DispatchMissionsUseCase $dispatchMissionsUseCase;

    protected function setUp(): void
    {
        $this->dispatchMissionsUseCase = new DispatchMissionsUseCase();

        parent::setUp();
    }

    public function testDispatchMissions(): void
    {
        for ($i = 0; $i <= self::DISPATCH_ITERATION; $i++) {
            $this->dispatchMissions();
        }
    }

    private function dispatchMissions(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $mission1Player1 = $this->prophesize(Mission::class);
        $mission2Player1 = $this->prophesize(Mission::class);

        $mission1Player2 = $this->prophesize(Mission::class);
        $mission2Player2 = $this->prophesize(Mission::class);

        /** @var Room|ObjectProphecy $room */
        $room = $this->prophesize(Room::class);

        $player1->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$mission1Player1->reveal(), $mission2Player1->reveal()]);
        $player1->getTarget()->shouldBeCalled()->willReturn($player2->reveal());
        $player1->setAssignedMission(Argument::any())->shouldBeCalled();

        $player2->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$mission1Player2->reveal(), $mission2Player2->reveal()]);
        $player2->getTarget()->shouldBeCalled()->willReturn($player3->reveal());
        $player2->setAssignedMission(Argument::any())->shouldBeCalled();

        $player3->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([]);
        $player3->getTarget()->shouldBeCalled()->willReturn($player1);
        $player3->setAssignedMission(Argument::any())->shouldBeCalled();

        $mission1Player1->getAuthor()->shouldBeCalled()->willReturn($player1->reveal());
        $mission2Player1->getAuthor()->shouldBeCalled()->willReturn($player1->reveal());

        $mission1Player2->getAuthor()->shouldBeCalled()->willReturn($player2->reveal());
        $mission2Player2->getAuthor()->shouldBeCalled()->willReturn($player2->reveal());

        $room->getPlayers()
            ->shouldBeCalled()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal(), $player3->reveal()]));

        $this->dispatchMissionsUseCase->execute($room->reveal());

        $this->assertNotNull($player1->getAssignedMission());
        $this->assertNotNull($player2->getAssignedMission());
    }
}
