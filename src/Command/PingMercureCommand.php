<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(name: 'app:ping-mercure', description: 'Ping mercure hub.')]
class PingMercureCommand extends Command
{
    public function __construct(private readonly HubInterface $mercureHub, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command ping mercure to test if config is correct.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->mercureHub->publish(new Update('topic', 'update'));
        } catch (\Exception $e) {
            $output->writeln('Could not ping mercure because of following error : ' . $e->getMessage());
            $output->writeln('Previous error was : ' . $e->getPrevious()?->getMessage());

            return Command::SUCCESS;
        }

        $output->writeln('Mercure ping was successfully done !');

        return Command::SUCCESS;
    }
}
