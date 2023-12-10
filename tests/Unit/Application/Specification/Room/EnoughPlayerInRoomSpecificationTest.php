<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Specification\Room;

use App\Application\Specification\Room\EnoughPlayerInRoomSpecification;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\PhpUnit\ProphecyTrait;

class EnoughPlayerInRoomSpecificationTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private EnoughPlayerInRoomSpecification $enoughPlayerInRoomSpecification;

    protected function setUp(): void
    {
        $this->enoughPlayerInRoomSpecification = new EnoughPlayerInRoomSpecification();
    }

    public function testIsSatisfiedBy(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);
        $player3 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getAlivePlayers()
            ->shouldBeCalledOnce()
            ->willReturn([$player1->reveal(), $player2->reveal(), $player3->reveal()]);

        $this->assertTrue($this->enoughPlayerInRoomSpecification->isSatisfiedBy($room->reveal()));
    }

    public function isNotEnoughPlayers(): void
    {
        $player1 = $this->prophesize(Player::class);
        $player2 = $this->prophesize(Player::class);

        $room = $this->prophesize(Room::class);
        $room->getPlayers()
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$player1->reveal(), $player2->reveal()]));

        $this->assertFalse($this->enoughPlayerInRoomSpecification->isSatisfiedBy($room->reveal()));
    }
}
