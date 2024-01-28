<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Mission\MissionRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear:missions', description: 'Clear dangling missions')]
class ClearMissionsCommand extends Command
{
    public function __construct(
        private readonly MissionRepository $missionRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('This command clear all orphans missions with no authors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orphansMissions = $this->missionRepository->findBy(['author' => null, 'isAssigned' => false]);
        $missionToDeleteCount = \count($orphansMissions);

        foreach ($orphansMissions as $mission) {
            $this->missionRepository->remove($mission);
        }

        $this->persistenceAdapter->flush();

        $output->writeln(sprintf('%d mission(s) successfully removed', $missionToDeleteCount));

        return Command::SUCCESS;
    }
}
