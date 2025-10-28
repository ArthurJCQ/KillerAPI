<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Player;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Application\UseCase\Player\SwitchMissionUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\MissionSwitchAlreadyUsedException;
use App\Domain\Player\Exception\PlayerHasNoMissionException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class SwitchMissionUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $missionGenerator;
    private ObjectProphecy $missionRepository;
    private ObjectProphecy $createMissionUseCase;
    private SwitchMissionUseCase $switchMissionUseCase;

    protected function setUp(): void
    {
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->missionGenerator = $this->prophesize(MissionGeneratorInterface::class);
        $this->missionRepository = $this->prophesize(MissionRepository::class);
        $this->createMissionUseCase = $this->prophesize(CreateMissionUseCase::class);

        $this->switchMissionUseCase = new SwitchMissionUseCase(
            $this->persistenceAdapter->reveal(),
            $this->missionGenerator->reveal(),
            $this->createMissionUseCase->reveal(),
            $this->missionRepository->reveal(),
        );
    }

    public function testExecuteSuccessfullyWithMissionFromPool(): void
    {
        $newMission = $this->make(Mission::class);

        $room = $this->make(Room::class, [
            'getId' => Expected::atLeastOnce('XXXXX'),
            'getStatus' => Expected::once(Room::IN_GAME),
            'popSecondaryMission' => Expected::once($newMission),
        ]);

        $currentMission = $this->make(Mission::class);

        $player = $this->make(Player::class, [
            'getId' => Expected::atLeastOnce(1),
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::atLeastOnce($room),
            'getAssignedMission' => Expected::once($currentMission),
            'hasMissionSwitchUsed' => Expected::once(false),
            'setAssignedMission' => Expected::once(new Player()),
            'setMissionSwitchUsed' => Expected::once(new Player()),
            'removePoints' => Expected::once(new Player()),
        ]);

        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->createMissionUseCase->execute(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $this->switchMissionUseCase->execute($player);
    }

    public function testExecuteSuccessfullyWithGeneratedMissionWhenPoolIsEmpty(): void
    {
        $generatedMission = $this->make(Mission::class);

        $room = $this->make(Room::class, [
            'getId' => Expected::atLeastOnce('XXXXX'),
            'getStatus' => Expected::once(Room::IN_GAME),
            'popSecondaryMission' => Expected::once(null),
        ]);

        $currentMission = $this->make(Mission::class);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::atLeastOnce($room),
            'getAssignedMission' => Expected::once($currentMission),
            'hasMissionSwitchUsed' => Expected::once(false),
            'setAssignedMission' => Expected::once(new Player()),
            'setMissionSwitchUsed' => Expected::once(new Player()),
            'removePoints' => Expected::once(new Player()),
        ]);

        $this->missionGenerator->generateMissions(1)
            ->shouldBeCalledOnce()
            ->willReturn(['Complete the mission while wearing sunglasses']);

        $this->createMissionUseCase->execute('Complete the mission while wearing sunglasses')
            ->shouldBeCalledOnce()
            ->willReturn($generatedMission);

        $this->missionRepository->store($generatedMission)->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $this->switchMissionUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenPlayerIsKilled(): void
    {
        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::KILLED),
        ]);

        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->createMissionUseCase->execute(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(PlayerKilledException::class);
        $this->expectExceptionMessage('PLAYER_IS_KILLED');

        $this->switchMissionUseCase->execute($player);
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

        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->createMissionUseCase->execute(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(RoomNotInGameException::class);
        $this->expectExceptionMessage('ROOM_NOT_IN_GAME');

        $this->switchMissionUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenPlayerHasNoMission(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'getAssignedMission' => Expected::once(null),
        ]);

        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->createMissionUseCase->execute(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(PlayerHasNoMissionException::class);
        $this->expectExceptionMessage('PLAYER_HAS_NO_MISSION');

        $this->switchMissionUseCase->execute($player);
    }

    public function testExecuteThrowsExceptionWhenMissionSwitchAlreadyUsed(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $currentMission = $this->make(Mission::class);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::once(PlayerStatus::ALIVE),
            'getRoom' => Expected::once($room),
            'getAssignedMission' => Expected::once($currentMission),
            'hasMissionSwitchUsed' => Expected::once(true),
        ]);

        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->createMissionUseCase->execute(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(MissionSwitchAlreadyUsedException::class);
        $this->expectExceptionMessage('MISSION_SWITCH_ALREADY_USED');

        $this->switchMissionUseCase->execute($player);
    }
}
