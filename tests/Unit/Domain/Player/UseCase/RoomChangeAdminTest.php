<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\NotEnoughPlayersInRoomException;
use App\Domain\Room\UseCase\RoomChangeAdminUseCase;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class RoomChangeAdminTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private RoomChangeAdminUseCase $roomChangeAdminUseCase;

    protected function setUp(): void
    {
        $this->roomChangeAdminUseCase = new RoomChangeAdminUseCase();

        parent::setUp();
    }

    public function testTransferRoleAdmin(): void
    {
        $adminPlayer = $this->prophesize(Player::class);
        $regularPlayer = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getAdmin()->shouldBeCalledOnce()->willReturn($adminPlayer->reveal());
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$adminPlayer->reveal(), $regularPlayer->reveal()]));
        $room->setAdmin($regularPlayer)->shouldBeCalledOnce();

        $adminPlayer->getId()->shouldBeCalled()->willReturn(1);
        $regularPlayer->getId()->shouldBeCalled()->willReturn(2);

        $this->roomChangeAdminUseCase->execute($room->reveal());
    }

    public function testNotEnoughPlayers(): void
    {
        $room = $this->prophesize(Room::class);
        $room->getAdmin()->shouldBeCalledOnce()->willReturn(null);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([]));
        $room->setAdmin(Argument::any())->shouldNotBeCalled();

        $this->roomChangeAdminUseCase->execute($room->reveal());
    }
}
