<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\Specification;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Specification\EnoughMissionInRoomSpecification;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class EnoughMissionInRoomSpecificationTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private ObjectProphecy $missionRepository;
    private EnoughMissionInRoomSpecification $enoughMissionInRoomSpecification;

    protected function setUp(): void
    {
        $this->missionRepository = $this->prophesize(MissionRepository::class);

        $this->enoughMissionInRoomSpecification = new EnoughMissionInRoomSpecification(
            $this->missionRepository->reveal(),
        );

        parent::setUp();
    }

    public function testIsSatisfiedBy(): void
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

        $this->missionRepository->getMissionsByRoomAndAuthor($room->reveal())->shouldBeCalledOnce()->willReturn([
            $mission1->reveal(),
            $mission2->reveal(),
        ]);

        $this->assertFalse($this->enoughMissionInRoomSpecification->isSatisfiedBy($room->reveal()));
    }

    public function notEnoughMissions(): void
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

        $this->missionRepository->getMissionsByRoomAndAuthor($room->reveal())->shouldBeCalledOnce()->willReturn([
            $mission1->reveal(),
            $mission2->reveal(),
        ]);

        $this->assertFalse($this->enoughMissionInRoomSpecification->isSatisfiedBy($room->reveal()));
    }

    public function notEnoughPlayersAddedMissions(): void
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

        $this->missionRepository->getMissionsByRoomAndAuthor($room->reveal())->shouldBeCalledOnce()->willReturn([
            $mission1->reveal(),
        ]);

        $this->assertFalse($this->enoughMissionInRoomSpecification->isSatisfiedBy($room->reveal()));
    }
}
