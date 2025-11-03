<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Player;

use App\Application\UseCase\Player\ContestKillUseCase;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ContestKillUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $persistenceAdapter;
    private ContestKillUseCase $contestKillUseCase;

    protected function setUp(): void
    {
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);

        $this->contestKillUseCase = new ContestKillUseCase($this->persistenceAdapter->reveal());

        parent::setUp();
    }

    public function testSuccessfulContestKill(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::DYING),
            'getRoom' => Expected::once($room),
            'setStatus' => Expected::once(new Player()->setStatus(PlayerStatus::ALIVE)),
            'getId' => Expected::atLeastOnce(1),
        ]);

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $this->contestKillUseCase->execute($player);
    }

    public function testThrowsExceptionWhenPlayerNotDying(): void
    {
        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(PlayerKilledException::class);
        $this->expectExceptionMessage('PLAYER_NOT_DYING');

        $this->contestKillUseCase->execute($player);
    }

    public function testThrowsExceptionWhenPlayerIsKilled(): void
    {
        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::KILLED),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(PlayerKilledException::class);
        $this->expectExceptionMessage('PLAYER_NOT_DYING');

        $this->contestKillUseCase->execute($player);
    }

    public function testThrowsExceptionWhenRoomNotInGame(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::PENDING),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::DYING),
            'getRoom' => Expected::once($room),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(RoomNotInGameException::class);
        $this->expectExceptionMessage('ROOM_NOT_IN_GAME');

        $this->contestKillUseCase->execute($player);
    }

    public function testThrowsExceptionWhenPlayerHasNoRoom(): void
    {
        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::DYING),
            'getRoom' => Expected::once(null),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(RoomNotInGameException::class);
        $this->expectExceptionMessage('ROOM_NOT_IN_GAME');

        $this->contestKillUseCase->execute($player);
    }
}
