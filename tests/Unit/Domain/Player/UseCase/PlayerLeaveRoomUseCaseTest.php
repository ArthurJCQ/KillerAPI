<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerLeftRoomEvent;
use App\Domain\Player\UseCase\PlayerLeaveRoomUseCase;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerLeaveRoomUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private ObjectProphecy $eventDispatcher;

    private PlayerLeaveRoomUseCase $playerLeaveRoomUseCase;

    protected function setUp(): void
    {
        $roomRepository = $this->prophesize(RoomRepository::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->playerLeaveRoomUseCase = new PlayerLeaveRoomUseCase(
            $roomRepository->reveal(),
            $this->eventDispatcher->reveal(),
        );

        parent::setUp();
    }

    public function testPlayerLeaveRoom(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $event = new PlayerLeftRoomEvent($player1->reveal(), $room->reveal());

        $room->getPlayers()->shouldBeCalledOnce()->willReturn(new ArrayCollection([$player1, $player2]));

        $this->eventDispatcher->dispatch($event, PlayerLeftRoomEvent::NAME)
            ->shouldBeCalledOnce()
            ->willReturn($event);

        $this->playerLeaveRoomUseCase->execute($player1->reveal(), $room->reveal());
    }

    public function testPlayerLeaveAndRemoveRoom(): void
    {
        $player = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);

        $room->getPlayers()->shouldBeCalledOnce()->willReturn(new ArrayCollection([$player]));

        $this->playerLeaveRoomUseCase->execute($player->reveal(), $room->reveal());
    }
}
