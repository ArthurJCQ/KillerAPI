<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\UseCase\Room\RoomChangeAdminUseCase;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'app:room:change-admin', description: 'Change Admin of a given room')]
class ChangeRoomAdminCommand extends Command
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly RoomChangeAdminUseCase $roomChangeAdminUseCase,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('This command helps changing the admin of a room.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $roomCodeQuestion = new Question('What is the room code: ', 'XXXXX');
        $playerNameQuestion = new Question('What is the new Admin player name: ', 'Arty');

        /** @var string $roomCode */
        $roomCode = $helper->ask($input, $output, $roomCodeQuestion);
        /** @var string $newAdminName */
        $newAdminName = $helper->ask($input, $output, $playerNameQuestion);

        $room = $this->roomRepository->find($roomCode);

        if (!$room instanceof Room) {
            $output->writeln('Room not found.');

            return Command::FAILURE;
        }

        $newAdmin = array_values(
            array_filter(
                $room->getPlayers()->toArray(),
                static fn (Player $player) => $player->getName() === $newAdminName,
            ),
        );

        if (\count($newAdmin) === 0) {
            $output->writeln(sprintf('Player %s not found in this room', $newAdminName));

            return Command::FAILURE;
        }

        if ($newAdmin[0] === $room->getAdmin()) {
            $output->writeln(sprintf('Player %s is already the admin of this room', $newAdminName));

            return Command::SUCCESS;
        }

        $this->roomChangeAdminUseCase->execute($room, $newAdmin[0]);

        $this->persistenceAdapter->flush();

        return Command::SUCCESS;
    }
}
