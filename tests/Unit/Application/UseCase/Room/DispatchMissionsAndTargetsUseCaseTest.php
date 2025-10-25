<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Room;

use App\Application\UseCase\Room\DispatchMissionsAndTargetsUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\NullLogger;

class DispatchMissionsAndTargetsUseCaseTest extends Unit
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

        $room = $this->prophesize(Room::class);
        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(false);

        $player1->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer1->reveal()]);
        $player1->getTarget()->willReturn($player2->reveal());
        $player1->setTarget(Argument::that(static fn (Player $player) => $player !== $player1->reveal()))
            ->shouldBeCalled();
        $player1->setAssignedMission(Argument::that(static fn (Mission $mission) => $mission->getAuthor()
            !== $player1->reveal()))
            ->shouldBeCalled();
        $player1->getAssignedMission()->shouldBeCalled();
        $player1->getId()->shouldBeCalledTimes(3)->willReturn(1);

        $player2->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer2->reveal()]);
        $player2->getTarget()->willReturn($player3->reveal());
        $player2->setTarget(Argument::that(static fn (Player $player) => $player !== $player2->reveal()))
            ->shouldBeCalled();
        $player2->setAssignedMission(Argument::that(static fn (Mission $mission) => $mission->getAuthor()
            !== $player2->reveal()))
            ->shouldBeCalled();
        $player2->getAssignedMission()->shouldBeCalled();
        $player2->getId()->shouldBeCalledTimes(3)->willReturn(2);


        $player3->getAuthoredMissionsInRoom()
            ->shouldBeCalled()
            ->willReturn([$missionPlayer3->reveal()]);
        $player3->getTarget()->willReturn($player1);
        $player3->setTarget(Argument::that(static fn (Player $player) => $player !== $player3->reveal()))
            ->shouldBeCalled();
        $player3->setAssignedMission(Argument::that(static fn (Mission $mission) => $mission->getAuthor()
            !== $player3->reveal()))
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

        $room->getAlivePlayers()
            ->shouldBeCalled()
            ->willReturn([$player1->reveal(), $player2->reveal(), $player3->reveal()]);

        $this->dispatchMissionsUseCase->execute($room->reveal());
    }

    public function testDispatchOneMissionPerPlayerWithGameMaster(): void
    {
        $admin = $this->prophesize(Player::class);
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $mission1 = $this->prophesize(Mission::class);
        $mission2 = $this->prophesize(Mission::class);
        $mission3 = $this->prophesize(Mission::class);

        $room = $this->prophesize(Room::class);
        $room->isGameMastered()->shouldBeCalledOnce()->willReturn(true);
        $room->getAdmin()->shouldBeCalledOnce()->willReturn($admin->reveal());

        $admin->getAuthoredMissionsInRoom()
            ->shouldBeCalledOnce()
            ->willReturn([$mission1->reveal(), $mission2->reveal(), $mission3->reveal()]);
        $admin->getTarget()->shouldNotBeCalled();
        $admin->setTarget(Argument::any())->shouldNotBeCalled();
        $admin->setAssignedMission(Argument::any())->shouldNotBeCalled();
        $admin->getAssignedMission()->shouldNotBeCalled();

        $player1->getAuthoredMissionsInRoom()->shouldNotBeCalled();
        $player1->getTarget()->willReturn($player2->reveal());
        $player1->setTarget(Argument::that(static fn (Player $player) => $player !== $player1->reveal()))
            ->shouldBeCalled();
        $player1->setAssignedMission(Argument::that(static fn (Mission $mission) => $mission->getAuthor()
            === $admin->reveal()))
            ->shouldBeCalled();
        $player1->getAssignedMission()->shouldBeCalled();
        $player1->getId()->shouldBeCalledTimes(3)->willReturn(1);

        $player2->getAuthoredMissionsInRoom()->shouldNotBeCalled();
        $player2->getTarget()->willReturn($player3->reveal());
        $player2->setTarget(Argument::that(static fn (Player $player) => $player !== $player2->reveal()))
            ->shouldBeCalled();
        $player2->setAssignedMission(Argument::that(static fn (Mission $mission) => $mission->getAuthor()
            === $admin->reveal()))
            ->shouldBeCalled();
        $player2->getAssignedMission()->shouldBeCalled();
        $player2->getId()->shouldBeCalledTimes(3)->willReturn(2);


        $player3->getAuthoredMissionsInRoom()->shouldNotBeCalled();
        $player3->getTarget()->willReturn($player1);
        $player3->setTarget(Argument::that(static fn (Player $player) => $player !== $player3->reveal()))
            ->shouldBeCalled();
        $player3->setAssignedMission(Argument::that(
            static fn (Mission $mission) => $mission->getAuthor() === $admin->reveal(),
        ))->shouldBeCalled();
        $player3->getAssignedMission()->shouldBeCalled();
        $player3->getId()->shouldBeCalledTimes(3)->willReturn(3);

        $mission1->isAssigned()->willReturn(false, true);
        $mission1->getAuthor()->shouldBeCalled()->willReturn($admin->reveal());
        $mission1->getId()->shouldBeCalledOnce()->willReturn(1);

        $mission2->isAssigned()->willReturn(false, true);
        $mission2->getAuthor()->shouldBeCalled()->willReturn($admin->reveal());
        $mission2->getId()->shouldBeCalledOnce()->willReturn(2);

        $mission3->isAssigned()->willReturn(false, true);
        $mission3->getAuthor()->shouldBeCalled()->willReturn($admin->reveal());
        $mission3->getId()->shouldBeCalledOnce()->willReturn(3);

        $room->getAlivePlayers()
            ->shouldBeCalled()
            ->willReturn([$player1->reveal(), $player2->reveal(), $player3->reveal()]);

        $this->dispatchMissionsUseCase->execute($room->reveal());
    }
}
