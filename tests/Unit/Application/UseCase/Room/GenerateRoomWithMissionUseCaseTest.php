<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Room;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Application\UseCase\Room\CreateRoomUseCase;
use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\Enum\MissionTheme;
use App\Domain\Mission\MissionGeneratorInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class GenerateRoomWithMissionUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $missionGenerator;
    private ObjectProphecy $createRoomUseCase;
    private ObjectProphecy $createMissionUseCase;
    private GenerateRoomWithMissionUseCase $generateRoomWithMissionUseCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->missionGenerator = $this->prophesize(MissionGeneratorInterface::class);
        $this->createRoomUseCase = $this->prophesize(CreateRoomUseCase::class);
        $this->createMissionUseCase = $this->prophesize(CreateMissionUseCase::class);

        $this->generateRoomWithMissionUseCase = new GenerateRoomWithMissionUseCase(
            $this->persistenceAdapter->reveal(),
            $this->missionGenerator->reveal(),
            $this->createRoomUseCase->reveal(),
            $this->createMissionUseCase->reveal(),
        );
    }

    public function testExecuteCreatesRoomWithMissions(): void
    {
        $player = $this->make(Player::class, [
            'getId' => 1,
            'getName' => 'TestPlayer',
        ]);

        $roomName = 'Test AI Room';
        $missionsCount = 5;
        $theme = MissionTheme::SPY;

        $room = $this->make(Room::class, [
            'getName' => $roomName,
            'isGameMastered' => true,
            'getId' => 'ROOM-123',
        ]);

        $generatedMissionContents = [
            'Mission 1 content',
            'Mission 2 content',
            'Mission 3 content',
            'Mission 4 content',
            'Mission 5 content',
        ];

        // Mock the room creation
        $this->createRoomUseCase->execute($player, $roomName, true)
            ->shouldBeCalledOnce()
            ->willReturn($room);

        // Mock the mission generation
        $this->missionGenerator->generateMissions($missionsCount, $theme)
            ->shouldBeCalledOnce()
            ->willReturn($generatedMissionContents);

        // Mock the mission creation for each generated mission
        foreach ($generatedMissionContents as $content) {
            $mission = new Mission();
            $mission->setContent($content);

            $this->createMissionUseCase->execute($content, $player)
                ->shouldBeCalledOnce()
                ->willReturn($mission);
        }

        // Mock persistence
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        // Execute the use case
        $result = $this->generateRoomWithMissionUseCase->execute($roomName, $player, $missionsCount, $theme);

        // Assertions
        $this->assertInstanceOf(Room::class, $result);
        $this->assertEquals($roomName, $result->getName());
        $this->assertTrue($result->isGameMastered());
    }

    public function testExecuteWithDefaultMissionsCount(): void
    {
        $player = $this->make(Player::class, [
            'getId' => 1,
            'getName' => 'TestPlayer',
        ]);

        $roomName = 'Test Room';

        $room = $this->make(Room::class, [
            'getName' => $roomName,
            'isGameMastered' => true,
            'getId' => 'ROOM-123',
        ]);

        $generatedMissionContents = array_fill(0, 10, 'Mission content');

        $this->createRoomUseCase->execute($player, $roomName, true)
            ->shouldBeCalledOnce()
            ->willReturn($room);

        // Should use default count of 10
        $this->missionGenerator->generateMissions(10, null)
            ->shouldBeCalledOnce()
            ->willReturn($generatedMissionContents);

        $this->createMissionUseCase->execute(Argument::any(), $player)
            ->shouldBeCalledTimes(10)
            ->willReturn(new Mission());

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $result = $this->generateRoomWithMissionUseCase->execute($roomName, $player);

        $this->assertInstanceOf(Room::class, $result);
    }

    public function testExecuteWithoutTheme(): void
    {
        $player = $this->make(Player::class, [
            'getId' => 1,
            'getName' => 'TestPlayer',
        ]);

        $roomName = 'Test Room';
        $missionsCount = 3;

        $room = $this->make(Room::class, [
            'getName' => $roomName,
            'isGameMastered' => true,
            'getId' => 'ROOM-123',
        ]);

        $generatedMissionContents = [
            'Mission 1 content',
            'Mission 2 content',
            'Mission 3 content',
        ];

        $this->createRoomUseCase->execute($player, $roomName, true)
            ->shouldBeCalledOnce()
            ->willReturn($room);

        // Should pass null for theme
        $this->missionGenerator->generateMissions($missionsCount, null)
            ->shouldBeCalledOnce()
            ->willReturn($generatedMissionContents);

        $this->createMissionUseCase->execute(Argument::any(), $player)
            ->shouldBeCalledTimes(3)
            ->willReturn(new Mission());

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $result = $this->generateRoomWithMissionUseCase->execute($roomName, $player, $missionsCount);

        $this->assertInstanceOf(Room::class, $result);
    }

    public function testExecuteThrowsExceptionOnFailure(): void
    {
        $player = $this->make(Player::class, [
            'getId' => 1,
            'getName' => 'TestPlayer',
        ]);

        $roomName = 'Test Room';

        $room = $this->make(Room::class, [
            'getName' => $roomName,
            'getId' => 'ROOM-123',
        ]);

        $this->createRoomUseCase->execute($player, $roomName, true)
            ->shouldBeCalledOnce()
            ->willReturn($room);

        // Mock a failure in mission generation
        $this->missionGenerator->generateMissions(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willThrow(new \RuntimeException('AI generation failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create game-mastered room');

        $this->generateRoomWithMissionUseCase->execute($roomName, $player);
    }
}
