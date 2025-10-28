<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Room;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Application\UseCase\Room\DispatchMissionsAndTargetsUseCase;
use App\Application\UseCase\Room\StartGameUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Mission\MissionRepository;
use App\Domain\Notifications\GameStartedNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;

class StartGameUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $dispatchMissionsAndTargetsUseCase;
    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $notifier;
    private ObjectProphecy $missionGenerator;
    private ObjectProphecy $createMissionUseCase;
    private ObjectProphecy $missionRepository;
    private StartGameUseCase $startGameUseCase;

    protected function setUp(): void
    {
        $this->dispatchMissionsAndTargetsUseCase = $this->prophesize(DispatchMissionsAndTargetsUseCase::class);
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->notifier = $this->prophesize(KillerNotifier::class);
        $this->missionGenerator = $this->prophesize(MissionGeneratorInterface::class);
        $this->createMissionUseCase = $this->prophesize(CreateMissionUseCase::class);
        $this->missionRepository = $this->prophesize(MissionRepository::class);

        $this->startGameUseCase = new StartGameUseCase(
            $this->dispatchMissionsAndTargetsUseCase->reveal(),
            $this->persistenceAdapter->reveal(),
            $this->notifier->reveal(),
            $this->missionGenerator->reveal(),
            $this->createMissionUseCase->reveal(),
            $this->missionRepository->reveal(),
        );

        $this->startGameUseCase->setLogger(new NullLogger());

        parent::setUp();
    }

    public function testExecuteStartsGameAndGeneratesSecondaryMissions(): void
    {
        $player1 = $this->prophesize(Player::class)->reveal();
        $player2 = $this->prophesize(Player::class)->reveal();
        $player3 = $this->prophesize(Player::class)->reveal();

        $room = $this->prophesize(Room::class);
        $room->getId()->willReturn('ABC12');
        $room->getAlivePlayers()->willReturn([$player1, $player2, $player3]);
        $room->getPlayers()->willReturn(new ArrayCollection([$player1, $player2, $player3]));
        $room->addSecondaryMission(Argument::type(Mission::class))->shouldBeCalledTimes(6);

        // Should dispatch missions and targets first
        $this->dispatchMissionsAndTargetsUseCase->execute($room->reveal())->shouldBeCalledOnce();

        // Should generate 6 missions (3 players * 2)
        $this->missionGenerator->generateMissions(6)
            ->shouldBeCalledOnce()
            ->willReturn([
                'Mission 1',
                'Mission 2',
                'Mission 3',
                'Mission 4',
                'Mission 5',
                'Mission 6',
            ]);

        // Should create 6 missions via CreateMissionUseCase
        $mission1 = $this->prophesize(Mission::class);
        $mission2 = $this->prophesize(Mission::class);
        $mission3 = $this->prophesize(Mission::class);
        $mission4 = $this->prophesize(Mission::class);
        $mission5 = $this->prophesize(Mission::class);
        $mission6 = $this->prophesize(Mission::class);

        $this->createMissionUseCase->execute('Mission 1')->shouldBeCalledOnce()->willReturn($mission1->reveal());
        $this->createMissionUseCase->execute('Mission 2')->shouldBeCalledOnce()->willReturn($mission2->reveal());
        $this->createMissionUseCase->execute('Mission 3')->shouldBeCalledOnce()->willReturn($mission3->reveal());
        $this->createMissionUseCase->execute('Mission 4')->shouldBeCalledOnce()->willReturn($mission4->reveal());
        $this->createMissionUseCase->execute('Mission 5')->shouldBeCalledOnce()->willReturn($mission5->reveal());
        $this->createMissionUseCase->execute('Mission 6')->shouldBeCalledOnce()->willReturn($mission6->reveal());

        // Should store each mission
        $this->missionRepository->store(Argument::type(Mission::class))->shouldBeCalledTimes(6);

        // Should flush persistence
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        // Should notify players
        $this->notifier->notify(Argument::type(GameStartedNotification::class))->shouldBeCalledOnce();

        $this->startGameUseCase->execute($room->reveal());
    }

    public function testExecuteGeneratesCorrectNumberOfSecondaryMissionsForDifferentPlayerCounts(): void
    {
        // Test with 5 players - should generate 10 missions
        $players = array_map(fn () => $this->prophesize(Player::class)->reveal(), range(1, 5));

        $room = $this->prophesize(Room::class);
        $room->getId()->willReturn('XYZ99');
        $room->getAlivePlayers()->willReturn($players);
        $room->getPlayers()->willReturn(new ArrayCollection($players));
        $room->addSecondaryMission(Argument::type(Mission::class))->shouldBeCalledTimes(10);

        $this->dispatchMissionsAndTargetsUseCase->execute($room->reveal())->shouldBeCalledOnce();

        $this->missionGenerator->generateMissions(10)
            ->shouldBeCalledOnce()
            ->willReturn(array_fill(0, 10, 'Test mission'));

        $mission = $this->prophesize(Mission::class);

        $this->createMissionUseCase->execute('Test mission')->willReturn($mission->reveal());
        $this->missionRepository->store(Argument::type(Mission::class))->shouldBeCalledTimes(10);
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();
        $this->notifier->notify(Argument::type(GameStartedNotification::class))->shouldBeCalledOnce();

        $this->startGameUseCase->execute($room->reveal());
    }
}
