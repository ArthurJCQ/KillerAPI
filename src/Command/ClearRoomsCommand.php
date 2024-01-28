<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear:rooms', description: 'Clear empty rooms')]
class ClearRoomsCommand extends Command
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('This command clear all rooms with no more players in it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emptyRooms = $this->roomRepository->getEmptyRooms();
        $i = 0;

        /** @var Room $room */
        foreach ($emptyRooms as $room) {
            $i++;
            $this->roomRepository->remove($room);
        }

        $this->persistenceAdapter->flush();

        $output->writeln(sprintf('%d room(s) were successfully removed', $i));

        return Command::SUCCESS;
    }
}
