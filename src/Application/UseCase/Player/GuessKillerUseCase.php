<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\KillerEliminatedByGuessNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\WrongGuessEliminatedNotification;
use App\Domain\Notifications\YourTargetEliminatedNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

readonly class GuessKillerUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private PersistenceAdapterInterface $persistenceAdapter,
        private KillerNotifier $killerNotifier,
    ) {
    }

    public function execute(Player $guesser, string $guessedPlayerId): void
    {
        // Validation: Player must be alive
        if ($guesser->getStatus() !== PlayerStatus::ALIVE) {
            throw new PlayerKilledException('PLAYER_IS_KILLED');
        }

        // Validation: Room must be in game
        $room = $guesser->getRoom();
        if ($room?->getStatus() !== Room::IN_GAME) {
            throw new RoomNotInGameException('ROOM_NOT_IN_GAME');
        }

        // Validation: Player must have a killer
        $actualKiller = $guesser->getKiller();
        if ($actualKiller === null) {
            throw new PlayerHasNoKillerOrTargetException('KILLER_NOT_FOUND');
        }

        // Check if the guess is correct
        $isCorrectGuess = $actualKiller->getId() === $guessedPlayerId;

        if ($isCorrectGuess) {
            $this->handleCorrectGuess($guesser, $actualKiller);
        } else {
            $this->handleWrongGuess($guesser, $actualKiller);
        }
    }

    private function handleCorrectGuess(Player $guesser, Player $killer): void
    {
        // The killer is eliminated
        $killer->setStatus(PlayerStatus::KILLED);

        // Get the killer's killer (the one who was hunting the eliminated killer)
        $killersKiller = $killer->getKiller();

        // Get the killer's target and mission to transfer
        $killersTarget = $killer->getTarget();
        $killersAssignedMission = $killer->getAssignedMission();

        // Remove killer's target and mission
        $killer->setTarget(null);
        $killer->setAssignedMission(null);

        // Transfer the killed killer's target and mission to their killer
        if ($killersKiller !== null && $killersTarget !== null) {
            $killersKiller->setTarget($killersTarget);
            $killersKiller->setAssignedMission($killersAssignedMission);
            $killersKiller->setMissionSwitchUsed(false);

            // Notify the killer's killer that their target has been eliminated
            $this->killerNotifier->notify(YourTargetEliminatedNotification::to($killersKiller));
        }

        $this->persistenceAdapter->flush();

        // Notify the eliminated killer
        $this->killerNotifier->notify(KillerEliminatedByGuessNotification::to($killer));

        $this->logger?->info(
            'Player {guesser} correctly guessed their killer {killer}',
            ['guesser' => $guesser->getId(), 'killer' => $killer->getId()]
        );
    }

    private function handleWrongGuess(Player $guesser, Player $actualKiller): void
    {
        // The guesser is eliminated for guessing wrong
        $guesser->setStatus(PlayerStatus::KILLED);

        // Get the guesser's target and mission to transfer to their killer
        $guessersTarget = $guesser->getTarget();
        $guessersAssignedMission = $guesser->getAssignedMission();

        // Remove guesser's target and mission
        $guesser->setTarget(null);
        $guesser->setAssignedMission(null);

        // Transfer the guesser's target and mission to the actual killer
        if ($guessersTarget !== null) {
            $actualKiller->setTarget($guessersTarget);
            $actualKiller->setAssignedMission($guessersAssignedMission);
            $actualKiller->setMissionSwitchUsed(false);
            $actualKiller->addPoints(10); // Award points for the elimination
        }

        $this->persistenceAdapter->flush();

        // Notify the killer that their target has been eliminated
        $this->killerNotifier->notify(WrongGuessEliminatedNotification::to($actualKiller));

        $this->logger?->info(
            'Player {guesser} incorrectly guessed their killer and was eliminated',
            ['guesser' => $guesser->getId(), 'actualKiller' => $actualKiller->getId()]
        );
    }
}
