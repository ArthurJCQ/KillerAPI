<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\KillerEliminatedByGuessNotification;
use App\Domain\Notifications\WrongGuessEliminatedNotification;
use App\Domain\Notifications\YourTargetEliminatedNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

readonly class GuessKillerUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private PlayerKilledUseCase $playerKilledUseCase,
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

        // Get the killer's killer to notify them
        $killersKiller = $killer->getKiller();

        // Use PlayerKilledUseCase to handle the elimination logic
        // Pass custom notifications and don't award points (killer was guessed, not killed by their hunter)
        $this->playerKilledUseCase->execute(
            player: $killer,
            killerNotification: KillerEliminatedByGuessNotification::to($killer),
            killersKillerNotification: $killersKiller !== null
                ? YourTargetEliminatedNotification::to($killersKiller)
                : null,
            awardPoints: false,
        );

        $this->logger?->info(
            'Player {guesser} correctly guessed their killer {killer}',
            ['guesser' => $guesser->getId(), 'killer' => $killer->getId()]
        );
    }

    private function handleWrongGuess(Player $guesser, Player $actualKiller): void
    {
        // The guesser is eliminated for guessing wrong
        $guesser->setStatus(PlayerStatus::KILLED);

        // Use PlayerKilledUseCase to handle the elimination logic
        // Pass custom notification and award points to the killer
        $this->playerKilledUseCase->execute(
            player: $guesser,
            killerNotification: WrongGuessEliminatedNotification::to($actualKiller),
            killersKillerNotification: null,
            awardPoints: true,
        );

        $this->logger?->info(
            'Player {guesser} incorrectly guessed their killer and was eliminated',
            ['guesser' => $guesser->getId(), 'actualKiller' => $actualKiller->getId()]
        );
    }
}
