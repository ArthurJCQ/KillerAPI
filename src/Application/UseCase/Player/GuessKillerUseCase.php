<?php

declare(strict_types=1);

namespace App\Application\UseCase\Player;

use App\Domain\Notifications\KillerEliminatedByGuessNotification;
use App\Domain\Notifications\KillerNotifier;
use App\Domain\Notifications\WrongGuessEliminatedNotification;
use App\Domain\Notifications\YourTargetEliminatedNotification;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Exception\PlayerHasNoKillerOrTargetException;
use App\Domain\Player\Exception\PlayerKilledException;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Exception\RoomNotInGameException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GuessKillerUseCase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly KillerNotifier $killerNotifier,
        private readonly PlayerRepository $playerRepository,
    ) {
        $this->logger = new NullLogger();
    }

    public function execute(Player $guesser, int $guessedPlayerId): void
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
        $actualKiller = $this->playerRepository->findKiller($guesser);

        if ($actualKiller === null) {
            throw new PlayerHasNoKillerOrTargetException('KILLER_NOT_FOUND');
        }

        // Check if the guess is correct
        $isCorrectGuess = $actualKiller->getId() === $guessedPlayerId;

        if ($isCorrectGuess) {
            $this->handleCorrectGuess($guesser, $actualKiller);

            return;
        }

        $this->handleWrongGuess($guesser, $actualKiller);
    }

    private function handleCorrectGuess(Player $guesser, Player $killer): void
    {
        // Award points to the player who correctly guessed their killer
        $guesser->addPoints(5);

        // The killer is eliminated
        $killer->setStatus(PlayerStatus::KILLED);

        // Get the killer's killer to notify them
        $killersKiller = $this->playerRepository->findKiller($killer);

        // Dispatch PlayerKilledEvent to handle the elimination logic
        // Pass custom notification and don't award points (killer was guessed, not killed by their hunter)
        $this->eventDispatcher->dispatch(
            new PlayerKilledEvent(
                player: $killer,
                room: null,
                killerNotification: KillerEliminatedByGuessNotification::to($killer),
                awardPoints: false,
            ),
        );

        if ($killersKiller === null) {
            throw new PlayerHasNoKillerOrTargetException('KILLER_NOT_FOUND');
        }

        $this->killerNotifier->notify(YourTargetEliminatedNotification::to($killersKiller));

        $this->logger?->info(
            'Player {guesser} correctly guessed their killer {killer}',
            ['guesser' => $guesser->getId(), 'killer' => $killer->getId()],
        );
    }

    private function handleWrongGuess(Player $guesser, Player $actualKiller): void
    {
        // The guesser is eliminated for guessing wrong
        $guesser->setStatus(PlayerStatus::KILLED);

        // Dispatch PlayerKilledEvent to handle the elimination logic
        // Pass custom notification and don't award points (wrong guess doesn't reward the killer)
        $this->eventDispatcher->dispatch(
            new PlayerKilledEvent(
                player: $guesser,
                room: null,
                killerNotification: WrongGuessEliminatedNotification::to($actualKiller),
                awardPoints: false,
            ),
        );

        $this->logger?->info(
            'Player {guesser} incorrectly guessed their killer and was eliminated',
            ['guesser' => $guesser->getId(), 'actualKiller' => $actualKiller->getId()],
        );
    }
}
