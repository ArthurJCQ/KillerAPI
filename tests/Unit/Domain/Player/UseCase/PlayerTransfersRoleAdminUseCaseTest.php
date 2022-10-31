<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\UseCase\PlayerTransfersRoleAdminUseCase;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\NotEnoughPlayersInRoomException;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Security;

class PlayerTransfersRoleAdminUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private ObjectProphecy $security;

    private PlayerTransfersRoleAdminUseCase $playerTransfersRoleAdminUseCase;

    protected function setUp(): void
    {
        $this->security = $this->prophesize(Security::class);
        $this->playerTransfersRoleAdminUseCase = new PlayerTransfersRoleAdminUseCase($this->security->reveal());

        parent::setUp();
    }

    public function testTransferRoleAdmin(): void
    {
        $adminPlayer = $this->prophesize(Player::class);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($adminPlayer);

        $regularPlayer = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$adminPlayer->reveal(), $regularPlayer->reveal()]));

        $adminPlayer->getRoom()->shouldBeCalledTimes(2)->willReturn($room->reveal());

        $adminPlayer->getId()->shouldBeCalled()->willReturn(1);
        $regularPlayer->getId()->shouldBeCalled()->willReturn(2);

        $adminPlayer->setRoles([Player::ROLE_PLAYER])->shouldBeCalledOnce();
        $regularPlayer->setRoles([Player::ROLE_ADMIN])->shouldBeCalledOnce();

        $this->playerTransfersRoleAdminUseCase->execute($adminPlayer->reveal());
    }

    public function testTransferRoleToSpecificPlayer(): void
    {
        $adminPlayer = $this->prophesize(Player::class);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($adminPlayer);

        $regularPlayer = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()->shouldNotBeCalled();

        $adminPlayer->getRoom()->shouldBeCalledOnce()->willReturn($room->reveal());

        $adminPlayer->getId()->shouldBeCalled()->willReturn(1);
        $regularPlayer->getId()->shouldBeCalled()->willReturn(2);

        $adminPlayer->setRoles([Player::ROLE_PLAYER])->shouldBeCalledOnce();
        $regularPlayer->setRoles([Player::ROLE_ADMIN])->shouldBeCalledOnce();

        $this->playerTransfersRoleAdminUseCase->execute($regularPlayer->reveal());
    }

    public function testNotEnoughPlayers(): void
    {
        $adminPlayer = $this->prophesize(Player::class);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($adminPlayer);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$adminPlayer->reveal()]));

        $adminPlayer->getRoom()->shouldBeCalledTimes(2)->willReturn($room->reveal());
        $adminPlayer->getId()->shouldBeCalledTimes(2)->willReturn(1);
        $adminPlayer->setRoles([Player::ROLE_PLAYER])->shouldNotBeCalled();
        $this->expectException(NotEnoughPlayersInRoomException::class);

        $this->playerTransfersRoleAdminUseCase->execute($adminPlayer->reveal());
    }

    public function testNoRoom(): void
    {
        $adminPlayer = $this->prophesize(Player::class);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($adminPlayer);

        $regularPlayer = $this->prophesize(Player::class);

        $adminPlayer->getRoom()->shouldBeCalledOnce()->willReturn(null);

        $adminPlayer->getId()->shouldNotBeCalled();
        $regularPlayer->getId()->shouldNotBeCalled();

        $adminPlayer->setRoles([Player::ROLE_PLAYER])->shouldNotBeCalled();
        $regularPlayer->setRoles([Player::ROLE_ADMIN])->shouldNotBeCalled();

        $this->playerTransfersRoleAdminUseCase->execute($adminPlayer->reveal());
    }
}
