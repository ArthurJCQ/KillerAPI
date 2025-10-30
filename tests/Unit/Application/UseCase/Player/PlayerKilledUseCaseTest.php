<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Player;

use App\Application\UseCase\Player\PlayerKilledUseCase;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Notifications\DeathConfirmationNotification;
use App\Domain\Notifications\KillerNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\WrongGuessEliminatedNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PlayerKilledUseCaseTest extends Unit
{
    use ProphecyTrait;

    private ObjectProphecy $persistenceAdapter;
    private ObjectProphecy $killerNotifier;
    private ObjectProphecy $playerRepository;
    private PlayerKilledUseCase $playerKilledUseCase;

    protected function setUp(): void
    {
        $this->persistenceAdapter = $this->prophesize(PersistenceAdapterInterface::class);
        $this->killerNotifier = $this->prophesize(KillerNotifier::class);
        $this->playerRepository = $this->prophesize(PlayerRepository::class);
        $this->playerKilledUseCase = new PlayerKilledUseCase(
            $this->persistenceAdapter->reveal(),
            $this->killerNotifier->reveal(),
            $this->playerRepository->reveal(),
        );

        parent::setUp();
    }

    public function testKillPlayer(): void
    {
        $mission = $this->makeEmpty(Mission::class);

        $killer = $this->make(Player::class, [
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
            'setMissionSwitchUsed' => Expected::once(new Player()),
            'addPoints' => Expected::once(new Player()),
        ]);

        $target = $this->make(Player::class);

        $player = $this->make(Player::class, [
            'getTarget' => Expected::once($target),
            'getAssignedMission' => Expected::once($mission),
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
        ]);

        // Mock repository to return killer
        $this->playerRepository->findKiller($player)->shouldBeCalledOnce()->willReturn($killer);

        $this->killerNotifier->notify(Argument::type(DeathConfirmationNotification::class))->shouldBeCalledOnce();

        $this->playerKilledUseCase->execute($player);
    }

    public function testKillPlayerWithCustomNotification(): void
    {
        $mission = $this->makeEmpty(Mission::class);

        $killer = $this->make(Player::class, [
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
            'setMissionSwitchUsed' => Expected::once(new Player()),
            'addPoints' => Expected::once(new Player()),
        ]);

        $target = $this->make(Player::class);

        $player = $this->make(Player::class, [
            'getTarget' => Expected::once($target),
            'getAssignedMission' => Expected::once($mission),
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
        ]);

        // Mock repository to return killer
        $this->playerRepository->findKiller($player)->shouldBeCalledOnce()->willReturn($killer);

        $this->playerKilledUseCase->execute($player, WrongGuessEliminatedNotification::to($killer));
    }

    public function testKillPlayerWithoutAwardingPoints(): void
    {
        $mission = $this->makeEmpty(Mission::class);

        $killer = $this->make(Player::class, [
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
            'setMissionSwitchUsed' => Expected::once(new Player()),
            'addPoints' => Expected::never(),
        ]);

        $target = $this->make(Player::class);

        $player = $this->make(Player::class, [
            'getTarget' => Expected::once($target),
            'getAssignedMission' => Expected::once($mission),
            'setTarget' => Expected::once(new Player()),
            'setAssignedMission' => Expected::once(new Player()),
        ]);

        // Mock repository to return killer
        $this->playerRepository->findKiller($player)->shouldBeCalledOnce()->willReturn($killer);

        $this->killerNotifier->notify(Argument::type(DeathConfirmationNotification::class))->shouldBeCalledOnce();

        $this->playerKilledUseCase->execute($player, null, false);
    }
}
