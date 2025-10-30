<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Player;

use App\Application\UseCase\Player\GuessKillerUseCase;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\YourTargetEliminatedNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GuessKillerUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $eventDispatcher;
    private ObjectProphecy $killerNotifier;
    private ObjectProphecy $playerRepository;
    private GuessKillerUseCase $guessKillerUseCase;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->killerNotifier = $this->prophesize(KillerNotifier::class);
        $this->playerRepository = $this->prophesize(PlayerRepository::class);

        $this->guessKillerUseCase = new GuessKillerUseCase(
            $this->eventDispatcher->reveal(),
            $this->killerNotifier->reveal(),
            $this->playerRepository->reveal(),
        );

        parent::setUp();
    }

    public function testCorrectGuess(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $killersKiller = $this->make(Player::class);

        $killer = $this->make(Player::class, [
            'getId' => Expected::atLeastOnce(1),
            'setStatus' => Expected::once(new Player()->setStatus(PlayerStatus::KILLED)),
        ]);

        $guesser = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'addPoints' => Expected::once(new Player()->addPoints(5)),
            'getId' => Expected::atLeastOnce(2),
        ]);

        // Mock repository findKiller calls
        $this->playerRepository->findKiller($guesser)->shouldBeCalledOnce()->willReturn($killer);
        $this->playerRepository->findKiller($killer)->shouldBeCalledOnce()->willReturn($killersKiller);

        $this->eventDispatcher->dispatch(Argument::type(PlayerKilledEvent::class))->shouldBeCalledOnce();
        $this->killerNotifier->notify(Argument::type(YourTargetEliminatedNotification::class))->shouldBeCalledOnce();

        $this->guessKillerUseCase->execute($guesser, 1);
    }

    public function testWrongGuess(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $killer = $this->make(Player::class, [
            'getId' => Expected::atLeastOnce(2),
        ]);

        $guesser = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'setStatus' => Expected::once(new Player()->setStatus(PlayerStatus::KILLED)),
            'getId' => Expected::atLeastOnce(2),
        ]);

        // Mock repository findKiller call
        $this->playerRepository->findKiller($guesser)->shouldBeCalledOnce()->willReturn($killer);

        $this->eventDispatcher->dispatch(Argument::type(PlayerKilledEvent::class))->shouldBeCalledOnce();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->guessKillerUseCase->execute($guesser, 3);
    }

    public function testThrowsExceptionWhenPlayerIsKilled(): void
    {
        $guesser = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::KILLED),
        ]);

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(PlayerKilledException::class);
        $this->expectExceptionMessage('PLAYER_IS_KILLED');

        $this->guessKillerUseCase->execute($guesser, 1);
    }

    public function testThrowsExceptionWhenRoomNotInGame(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::PENDING),
        ]);

        $guesser = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
        ]);

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(RoomNotInGameException::class);
        $this->expectExceptionMessage('ROOM_NOT_IN_GAME');

        $this->guessKillerUseCase->execute($guesser, 1);
    }

    public function testThrowsExceptionWhenKillerNotFound(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $guesser = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
        ]);

        // Mock repository returning null (no killer found)
        $this->playerRepository->findKiller($guesser)->shouldBeCalledOnce()->willReturn(null);

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->killerNotifier->notify(Argument::any())->shouldNotBeCalled();

        $this->expectException(PlayerHasNoKillerOrTargetException::class);
        $this->expectExceptionMessage('KILLER_NOT_FOUND');

        $this->guessKillerUseCase->execute($guesser, 1);
    }
}
