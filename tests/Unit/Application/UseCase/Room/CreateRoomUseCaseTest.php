<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Room;

use App\Application\UseCase\Player\ChangeRoomUseCase;
use App\Application\UseCase\Room\CreateRoomUseCase;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CreateRoomUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $roomRepository;
    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $changeRoomUseCase;
    private CreateRoomUseCase $createRoomUseCase;

    protected function setUp(): void
    {
        $this->roomRepository = $this->prophesize(RoomRepository::class);
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->changeRoomUseCase = $this->prophesize(ChangeRoomUseCase::class);

        $this->createRoomUseCase = new CreateRoomUseCase(
            $this->roomRepository->reveal(),
            $this->persistenceAdapter->reveal(),
            $this->changeRoomUseCase->reveal(),
        );

        parent::setUp();
    }

    public function testExecuteCreatesRegularRoom(): void
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $roomName = 'Test Room';

        $this->changeRoomUseCase->execute(
            Argument::that(static fn ($p) => $p === $player),
            Argument::type(Room::class),
        )->shouldBeCalledOnce();

        $this->roomRepository->store(Argument::type(Room::class))->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $room = $this->createRoomUseCase->execute($player, $roomName, false);

        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals($roomName, $room->getName());
        $this->assertFalse($room->isGameMastered());
        $this->assertContains('ROLE_ADMIN', $player->getRoles());
        $this->assertNotEquals(PlayerStatus::SPECTATING, $player->getStatus());
    }

    public function testExecuteCreatesGameMasteredRoom(): void
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $roomName = 'Game Master Room';

        $this->changeRoomUseCase->execute(
            Argument::that(static fn ($p) => $p === $player),
            Argument::type(Room::class),
        )->shouldBeCalledOnce();

        $this->roomRepository->store(Argument::type(Room::class))->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $room = $this->createRoomUseCase->execute($player, $roomName, true);

        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals($roomName, $room->getName());
        $this->assertTrue($room->isGameMastered());
        $this->assertContains('ROLE_MASTER', $player->getRoles());
        $this->assertEquals(PlayerStatus::SPECTATING, $player->getStatus());
    }

    public function testExecuteSetPlayerAsAdmin(): void
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $roomName = 'Admin Room';

        $this->changeRoomUseCase->execute(Argument::cetera())->shouldBeCalledOnce();
        $this->roomRepository->store(Argument::type(Room::class))->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $this->createRoomUseCase->execute($player, $roomName, false);

        $this->assertContains('ROLE_ADMIN', $player->getRoles());
    }

    public function testExecutePersistsRoom(): void
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $roomName = 'Persist Test Room';

        $this->changeRoomUseCase->execute(Argument::cetera())->shouldBeCalledOnce();

        $capturedRoom = null;
        $this->roomRepository->store(Argument::type(Room::class))
            ->will(static function ($args) use (&$capturedRoom): void {
                $capturedRoom = $args[0];
            })
            ->shouldBeCalledOnce();

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $room = $this->createRoomUseCase->execute($player, $roomName, false);

        $this->assertSame($room, $capturedRoom);
    }
}
