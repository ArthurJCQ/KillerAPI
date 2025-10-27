<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Mission;

use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Codeception\Test\Unit;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\NullLogger;

class CreateMissionUseCaseTest extends Unit
{
    use ProphecyTrait;

    private CreateMissionUseCase $createMissionUseCase;

    protected function setUp(): void
    {
        $this->createMissionUseCase = new CreateMissionUseCase();
        $this->createMissionUseCase->setLogger(new NullLogger());

        parent::setUp();
    }

    public function testExecuteCreatesMissionWithContentAndAuthor(): void
    {
        $author = $this->prophesize(Player::class);
        $content = 'Complete the mission while wearing sunglasses';

        $mission = $this->createMissionUseCase->execute($content, $author->reveal());

        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertSame($content, $mission->getContent());
        $this->assertSame($author->reveal(), $mission->getAuthor());
    }

    public function testExecuteCreatesMissionWithContentOnly(): void
    {
        $content = 'Eliminate your target while dancing';

        $mission = $this->createMissionUseCase->execute($content);

        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertSame($content, $mission->getContent());
        $this->assertNull($mission->getAuthor());
    }

    public function testExecuteCreatesMissionWithNullAuthor(): void
    {
        $content = 'Complete the mission in complete silence';

        $mission = $this->createMissionUseCase->execute($content, null);

        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertSame($content, $mission->getContent());
        $this->assertNull($mission->getAuthor());
    }
}
