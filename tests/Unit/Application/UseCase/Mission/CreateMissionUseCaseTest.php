<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Mission;

use App\Api\Exception\KillerBadRequestHttpException;
use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CreateMissionUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $missionRepository;
    private ObjectProphecy $persistenceAdapter;
    private CreateMissionUseCase $createMissionUseCase;

    protected function setUp(): void
    {
        $this->missionRepository = $this->prophesize(MissionRepository::class);
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);

        $this->createMissionUseCase = new CreateMissionUseCase(
            $this->missionRepository->reveal(),
            $this->persistenceAdapter->reveal(),
        );

        parent::setUp();
    }

    public function testExecuteCreatesMissionSuccessfully(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::atLeastOnce(Room::PENDING),
        ]);

        $author = null;
        $author = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($room),
            'addAuthoredMission' => Expected::once(static function ($mission) use (&$author) {
                return $author;
            }),
        ]);

        $missionContent = 'Test mission content';

        $this->missionRepository->store(Argument::type(Mission::class))->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $mission = $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertEquals($missionContent, $mission->getContent());
    }

    public function testExecuteThrowsExceptionWhenPlayerHasNoRoom(): void
    {
        $author = $this->make(Player::class, [
            'getRoom' => Expected::once(null),
        ]);

        $missionContent = 'Test mission content';

        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(KillerBadRequestHttpException::class);
        $this->expectExceptionMessage('CAN_NOT_ADD_MISSIONS');

        $this->createMissionUseCase->execute($missionContent, $author);
    }

    public function testExecuteThrowsExceptionWhenRoomNotPending(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::atLeastOnce(Room::IN_GAME),
        ]);

        $author = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($room),
        ]);

        $missionContent = 'Test mission content';

        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(KillerBadRequestHttpException::class);
        $this->expectExceptionMessage('CAN_NOT_ADD_MISSIONS');

        $this->createMissionUseCase->execute($missionContent, $author);
    }

    public function testExecuteThrowsExceptionWhenRoomIsEnded(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::atLeastOnce(Room::ENDED),
        ]);

        $author = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($room),
        ]);

        $missionContent = 'Test mission content';

        $this->missionRepository->store(Argument::any())->shouldNotBeCalled();
        $this->persistenceAdapter->flush()->shouldNotBeCalled();

        $this->expectException(KillerBadRequestHttpException::class);
        $this->expectExceptionMessage('CAN_NOT_ADD_MISSIONS');

        $this->createMissionUseCase->execute($missionContent, $author);
    }

    public function testExecuteAssociatesMissionWithAuthor(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::atLeastOnce(Room::PENDING),
        ]);

        $missionAddedToAuthor = false;
        $author = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($room),
            'addAuthoredMission' => Expected::once(
                static function (Mission $mission) use (&$missionAddedToAuthor, &$author) {
                    $missionAddedToAuthor = true;

                    return $author;
                },
            ),
        ]);

        $missionContent = 'Associated mission';

        $this->missionRepository->store(Argument::type(Mission::class))->shouldBeCalledOnce();
        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertTrue($missionAddedToAuthor);
    }

    public function testExecutePersistsMission(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::atLeastOnce(Room::PENDING),
        ]);

        $author = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($room),
            'addAuthoredMission' => Expected::once(static function ($mission) use (&$author) {
                return $author;
            }),
        ]);

        $missionContent = 'Persisted mission';

        $capturedMission = null;
        $this->missionRepository->store(Argument::type(Mission::class))
            ->will(static function ($args) use (&$capturedMission): void {
                $capturedMission = $args[0];
            })
            ->shouldBeCalledOnce();

        $this->persistenceAdapter->flush()->shouldBeCalledOnce();

        $mission = $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertSame($mission, $capturedMission);
        $this->assertEquals($missionContent, $capturedMission->getContent());
    }
}
