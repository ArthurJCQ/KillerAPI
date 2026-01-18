<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\Voters;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use App\Infrastructure\Security\Voters\RoomVoter;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RoomVoterSpectateTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $playerRepository;
    private RoomVoter $roomVoter;

    protected function setUp(): void
    {
        $this->playerRepository = $this->prophesize(PlayerRepository::class);
        $this->roomVoter = new RoomVoter($this->playerRepository->reveal());
        parent::setUp();
    }

    public function testCanSpectateWhenAllowedAndSpectatingStatus(): void
    {
        $room = $this->make(Room::class, [
            'isAllowSpectators' => Expected::atLeastOnce(true),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::atLeastOnce(PlayerStatus::SPECTATING),
            'getRoom' => Expected::atLeastOnce($room),
        ]);

        $user = $this->prophesize(User::class);

        $this->playerRepository->getCurrentUserPlayer($user->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($player);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $result = $this->roomVoter->vote($token->reveal(), $room, [RoomVoter::SPECTATE_ROOM]);

        $this->assertEquals(1, $result); // VoterInterface::ACCESS_GRANTED
    }

    public function testCannotSpectateWhenNotAllowed(): void
    {
        $room = $this->make(Room::class, [
            'isAllowSpectators' => Expected::atLeastOnce(false),
        ]);

        // Due to short-circuit evaluation, getStatus and getRoom won't be called
        // when isAllowSpectators returns false
        $player = $this->make(Player::class);

        $user = $this->prophesize(User::class);

        $this->playerRepository->getCurrentUserPlayer($user->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($player);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $result = $this->roomVoter->vote($token->reveal(), $room, [RoomVoter::SPECTATE_ROOM]);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }

    public function testCannotSpectateWhenNotSpectatingStatus(): void
    {
        $room = $this->make(Room::class, [
            'isAllowSpectators' => Expected::atLeastOnce(true),
        ]);

        $player = $this->make(Player::class, [
            'getStatus' => Expected::atLeastOnce(PlayerStatus::ALIVE),
            'getRoom' => Expected::atLeastOnce($room),
        ]);

        $user = $this->prophesize(User::class);

        $this->playerRepository->getCurrentUserPlayer($user->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($player);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $result = $this->roomVoter->vote($token->reveal(), $room, [RoomVoter::SPECTATE_ROOM]);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }

    public function testCannotSpectateWhenNotInRoom(): void
    {
        $room = $this->make(Room::class, [
            'isAllowSpectators' => Expected::atLeastOnce(true),
        ]);

        $otherRoom = $this->make(Room::class);

        // Due to short-circuit evaluation, getStatus won't be called
        // when player->getRoom() !== $room
        $player = $this->make(Player::class, [
            'getRoom' => Expected::atLeastOnce($otherRoom),
        ]);

        $user = $this->prophesize(User::class);

        $this->playerRepository->getCurrentUserPlayer($user->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($player);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $result = $this->roomVoter->vote($token->reveal(), $room, [RoomVoter::SPECTATE_ROOM]);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }

    public function testCannotSpectateWhenNoPlayer(): void
    {
        $room = $this->make(Room::class);

        $user = $this->prophesize(User::class);

        $this->playerRepository->getCurrentUserPlayer($user->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $result = $this->roomVoter->vote($token->reveal(), $room, [RoomVoter::SPECTATE_ROOM]);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }
}
