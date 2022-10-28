<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\UseCase\PlayerKilledUseCase;
use App\Domain\Player\UseCase\PlayerLeaveRoomUseCase;
use App\Domain\Player\UseCase\PlayerTransfersRoleAdminUseCase;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PlayerLeaveRoomUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private readonly ObjectProphecy $playerTransfersRoleAdminUseCase;
    private readonly ObjectProphecy $playerKilledUseCase;
    private readonly ObjectProphecy $roomRepository;

    private PlayerLeaveRoomUseCase $playerLeaveRoomUseCase;

    protected function setUp(): void
    {
        $this->playerTransfersRoleAdminUseCase = $this->prophesize(PlayerTransfersRoleAdminUseCase::class);
        $this->playerKilledUseCase = $this->prophesize(PlayerKilledUseCase::class);
        $this->roomRepository = $this->prophesize(RoomRepository::class);

        $this->playerLeaveRoomUseCase = new PlayerLeaveRoomUseCase(
            $this->playerTransfersRoleAdminUseCase->reveal(),
            $this->playerKilledUseCase->reveal(),
            $this->roomRepository->reveal(),
        );

        parent::setUp();
    }

    public function testPlayerLeaveRoom(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);

        $room->getPlayers()->shouldBeCalledOnce()->willReturn(new ArrayCollection([$player1, $player2]));
        $this->roomRepository->remove(Argument::any())->shouldNotBeCalled();

        $this->playerKilledUseCase->execute($player1->reveal())->shouldBeCalledOnce();

        $player1->getRoom()->shouldBeCalledOnce()->willReturn($room->reveal());
        $player1->setStatus(PlayerStatus::ALIVE)->shouldBeCalledOnce();
        $player1->isAdmin()->shouldBeCalledOnce()->willReturn(true);
        $player1->setRoles([Player::ROLE_PLAYER])->shouldBeCalledOnce();

        $this->playerTransfersRoleAdminUseCase->execute($player1)->shouldBeCalledOnce();

        $this->playerLeaveRoomUseCase->execute($player1->reveal());
    }

    public function testPlayerLeaveAndRemoveRoom(): void
    {
        $player = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);

        $room->getPlayers()->shouldBeCalledOnce()->willReturn(new ArrayCollection([$player]));
        $this->roomRepository->remove($room->reveal())->shouldBeCalledOnce();

        $this->playerKilledUseCase->execute($player->reveal())->shouldBeCalledOnce();

        $player->getRoom()->shouldBeCalledTimes(2)->willReturn($room->reveal());
        $player->setStatus(PlayerStatus::ALIVE)->shouldBeCalledOnce();
        $player->isAdmin()->shouldBeCalledOnce()->willReturn(false);
        $player->setRoles([Player::ROLE_PLAYER])->shouldBeCalledOnce();

        $this->playerTransfersRoleAdminUseCase->execute(Argument::any())->shouldNotBeCalled();

        $this->playerLeaveRoomUseCase->execute($player->reveal());
    }
}
