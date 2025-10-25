<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Event\PlayerUpdatedEvent;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'app:kill:player', description: 'Kill a player in a given room')]
class KillPlayerInRoomCommand extends Command
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly EventDispatcherInterface $eventDispatcher,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('This command kills a player in a given room. Arguments : player name & room code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $roomCodeQuestion = new Question('What is the room code: ', 'XXXXX');
        $playerNameQuestion = new Question('What is the player name: ', 'Arty');

        /** @var string $roomCode */
        $roomCode = $helper->ask($input, $output, $roomCodeQuestion);
        /** @var string $playerName */
        $playerName = $helper->ask($input, $output, $playerNameQuestion);

        $room = $this->roomRepository->find($roomCode);

        if (!$room instanceof Room) {
            $output->writeln('Room not found.');

            return Command::FAILURE;
        }

        if ($room->getStatus() !== Room::IN_GAME) {
            $output->writeln('Room must be IN_GAME');

            return Command::FAILURE;
        }

        $player = array_values(
            array_filter(
                $room->getPlayers()->toArray(),
                static fn (Player $player) => $player->getName() === $playerName,
            ),
        );

        if (\count($player) === 0) {
            $output->writeln(sprintf('Player %s not found in this room', $playerName));

            return Command::FAILURE;
        }

        $player[0]->setStatus(PlayerStatus::KILLED);
        $this->eventDispatcher->dispatch(new PlayerKilledEvent($player[0], $room));
        $this->eventDispatcher->dispatch(new PlayerUpdatedEvent($player[0]));

        $this->persistenceAdapter->flush();

        return Command::SUCCESS;
    }
}
