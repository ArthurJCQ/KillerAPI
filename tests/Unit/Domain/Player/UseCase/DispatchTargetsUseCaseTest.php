<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Player\UseCase;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\UseCase\DispatchTargetsUseCase;
use App\Domain\Room\Entity\Room;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class DispatchTargetsUseCaseTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private readonly DispatchTargetsUseCase $dispatchTargetsUseCase;

    protected function setUp(): void
    {
        $this->dispatchTargetsUseCase = new DispatchTargetsUseCase();

        parent::setUp();
    }

    public function testDispatchTargets(): void
    {
        $room = $this->prophesize(Room::class);

        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $player1->setTarget(Argument::type(Player::class))->shouldBeCalledOnce();
        $player2->setTarget(Argument::type(Player::class))->shouldBeCalledOnce();
        $player3->setTarget(Argument::type(Player::class))->shouldBeCalledOnce();

        $room->getPlayers()->shouldBeCalledOnce()->willReturn(new ArrayCollection([
            $player1->reveal(),
            $player2->reveal(),
            $player3->reveal(),
        ]));

        $this->dispatchTargetsUseCase->execute($room->reveal());
    }
}
