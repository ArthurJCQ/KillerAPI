<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase;

use App\Entity\Mission;
use App\Entity\Player;
use App\Entity\Room;
use App\Exception\PlayerHasNoKillerOrTargetException;
use App\UseCase\PlayerKilledUseCase;
use Codeception\Stub\Expected;

class PlayerKilledUseCaseTest extends \Codeception\Test\Unit
{
    private PlayerKilledUseCase $playerKilledUseCase;

    protected function setUp(): void
    {
        $this->playerKilledUseCase = new PlayerKilledUseCase();

        parent::setUp();
    }

    public function testPlayerHasNoRoom(): void
    {
        $player = $this->make(Player::class, [
            'getRoom' => Expected::once(),
            'getKiller' => Expected::never(),
            'getTarget' => Expected::never(),
            'getAssignedMission' => Expected::never(),
        ]);

        $this->playerKilledUseCase->execute($player);
    }

    public function testKillPlayer(): void
    {
        $mission = $this->makeEmpty(Mission::class);

        $killer = $this->make(Player::class, [
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
        ]);

        $target = $this->make(Player::class);

        $room = $this->make(Room::class, [
           'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getRoom' => Expected::exactly(2, $room),
            'getKiller' => Expected::once($killer),
            'getTarget' => Expected::once($target),
            'getAssignedMission' => Expected::once($mission),
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
        ]);

        $this->playerKilledUseCase->execute($player);
    }

    public function testNoKillerException(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getId' => Expected::once(1),
            'getRoom' => Expected::exactly(2, $room),
            'getKiller' => Expected::once(),
            'getTarget' => Expected::once(new Player()),
        ]);

        $this->expectException(PlayerHasNoKillerOrTargetException::class);

        $this->playerKilledUseCase->execute($player);
    }

    public function testNoTargetException(): void
    {
        $room = $this->make(Room::class, [
            'getStatus' => Expected::once(Room::IN_GAME),
        ]);

        $player = $this->make(Player::class, [
            'getId' => Expected::once(1),
            'getRoom' => Expected::exactly(2, $room),
            'getKiller' => Expected::once(new Player()),
            'getTarget' => Expected::once(),
        ]);

        $this->expectException(PlayerHasNoKillerOrTargetException::class);

        $this->playerKilledUseCase->execute($player);
    }
}
