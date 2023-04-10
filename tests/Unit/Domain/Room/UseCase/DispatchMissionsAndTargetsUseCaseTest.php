<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\UseCase;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\UseCase\DispatchMissionsAndTargetsUseCase;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;

class DispatchMissionsAndTargetsUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private DispatchMissionsAndTargetsUseCase $dispatchMissionsUseCase;

    protected function setUp(): void
    {
        $this->dispatchMissionsUseCase = new DispatchMissionsAndTargetsUseCase();
        $this->dispatchMissionsUseCase->setLogger(new NullLogger());

        parent::setUp();
    }

    public function testDispatchOneMissionPerPlayer(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $missionPlayer1 = $this->prophesize(Mission::class);
        $missionPlayer2 = $this->prophesize(Mission::class);
        $missionPlayer3 = $this->prophesize(Mission::class);

        /** @var Room|ObjectProphecy $room */
        $room = $this->prophesize(Room::class);

        $player1->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer1->reveal()]);
        $player1->getTarget()->willReturn($player2->reveal());
        $player1->setTarget(Argument::that(static fn (Player $player) =>
                $player instanceof Player && $player !== $player1->reveal()))
            ->shouldBeCalled();
        $player1->setAssignedMission(Argument::that(static fn (Mission $mission) =>
                $mission->getAuthor() !== $player1->reveal()))
            ->shouldBeCalled();
        $player1->getAssignedMission()->shouldBeCalled();
        $player1->getId()->shouldBeCalledTimes(3)->willReturn(1);


        $player2->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer2->reveal()]);
        $player2->getTarget()->willReturn($player3->reveal());
        $player2->setTarget(Argument::that(static fn (Player $player) =>
                $player instanceof Player && $player !== $player2->reveal()))
            ->shouldBeCalled();
        $player2->setAssignedMission(Argument::that(static fn (Mission $mission) =>
                $mission->getAuthor() !== $player2->reveal()))
            ->shouldBeCalled();
        $player2->getAssignedMission()->shouldBeCalled();
        $player2->getId()->shouldBeCalledTimes(3)->willReturn(2);


        $player3->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer3->reveal()]);
        $player3->getTarget()->willReturn($player1);
        $player3->setTarget(Argument::that(static fn (Player $player) =>
                $player instanceof Player && $player !== $player3->reveal()))
            ->shouldBeCalled();
        $player3->setAssignedMission(Argument::that(static fn (Mission $mission) =>
                $mission->getAuthor() !== $player3->reveal()))
            ->shouldBeCalled();
        $player3->getAssignedMission()->shouldBeCalled();
        $player3->getId()->shouldBeCalledTimes(3)->willReturn(3);


        $missionPlayer1->isAssigned()->shouldBeCalled();
        $missionPlayer1->getAuthor()->shouldBeCalled()->willReturn($player1->reveal());
        $missionPlayer1->getId()->shouldBeCalledOnce()->willReturn(1);

        $missionPlayer2->isAssigned()->shouldBeCalled();
        $missionPlayer2->getAuthor()->shouldBeCalled()->willReturn($player2->reveal());
        $missionPlayer2->getId()->shouldBeCalledOnce()->willReturn(2);

        $missionPlayer3->isAssigned()->shouldBeCalled();
        $missionPlayer3->getAuthor()->shouldBeCalled()->willReturn($player2->reveal());
        $missionPlayer3->getId()->shouldBeCalledOnce()->willReturn(3);

        $room->getPlayers()
            ->shouldBeCalled()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal(), $player3->reveal()]));

        $this->dispatchMissionsUseCase->execute($room->reveal());
    }
}
