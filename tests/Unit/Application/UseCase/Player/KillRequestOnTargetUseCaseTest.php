<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Player;

use App\Application\UseCase\Player\KillRequestOnTargetUseCase;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\KillRequestNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class KillRequestOnTargetUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $killerNotifier;
    private KillRequestOnTargetUseCase $killRequestOnTargetUseCase;

    protected function setUp(): void
    {
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->killerNotifier = $this->prophesize(KillerNotifier::class);

        $this->killRequestOnTargetUseCase = new KillRequestOnTargetUseCase(
            $this->persistenceAdapter->reveal(),
            $this->killerNotifier->reveal(),
        );

        parent::setUp();
    }

    public function testExecuteSuccessfully(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $target = $this->make(Player::class, [
            'setStatus' => Expected::once((new Player())->setStatus(PlayerStatus::DYING)),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'getTarget' => Expected::once($target),
        ]);

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();
        $this->killerNotifier->notify(Argument::type(KillRequestNotification::class))->shouldBeCalledOnce();

        $this->killRequestOnTargetUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenPlayerIsKilled(): void
    {
        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::KILLED),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(PlayerKilledException::class);
        $this->expectExceptionMessage('PLAYER_IS_KILLED');

        $this->killRequestOnTargetUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenRoomNotInGame(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::PENDING),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(RoomNotInGameException::class);
        $this->expectExceptionMessage('ROOM_NOT_IN_GAME');

        $this->killRequestOnTargetUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenTargetNotFound(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'getTarget' => Expected::once(null),
        ]);

        $this->persistenceAdapter->flush()->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(PlayerHasNoKillerOrTargetException::class);
        $this->expectExceptionMessage('TARGET_NOT_FOUND');

        $this->killRequestOnTargetUseCase->execute($player);
    }
}
