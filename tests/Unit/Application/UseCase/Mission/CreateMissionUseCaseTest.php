<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Mission;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Player\Entity\Player;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\PhpUnit\ProphecyTrait;

class CreateMissionUseCaseTest extends Unit
{
    use ProphecyTrait;

    private CreateMissionUseCase $createMissionUseCase;

    protected function setUp(): void
    {
        $this->createMissionUseCase = new CreateMissionUseCase();

        parent::setUp();
    }

    public function testExecuteCreatesMissionSuccessfully(): void
    {
        $author = null;
        $author = $this->make(Player::class, [
            'getId' => 1,
            'addAuthoredMission' => Expected::once(static function () use (&$author) {
                return $author;
            }),
        ]);

        $missionContent = 'Test mission content';

        $mission = $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertEquals($missionContent, $mission->getContent());
    }

    public function testExecuteAssociatesMissionWithAuthor(): void
    {
        $missionAddedToAuthor = false;
        $author = $this->make(Player::class, [
            'getId' => 1,
            'addAuthoredMission' => Expected::once(
                static function () use (&$missionAddedToAuthor, &$author) {
                    $missionAddedToAuthor = true;

                    return $author;
                },
            ),
        ]);

        $missionContent = 'Associated mission';

        $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertTrue($missionAddedToAuthor);
    }

    public function testExecuteAssociatesMissionWithoutAuthor(): void
    {
        $author = null;
        $missionContent = 'Associated mission';

        $mission = $this->createMissionUseCase->execute($missionContent, $author);

        $this->assertEquals($missionContent, $mission->getContent());
    }
}
