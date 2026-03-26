<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Command;

use KimaiPlugin\TaskBundle\Service\TaskRecurrenceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kimai:tasks:process-recurring',
    description: 'Process recurring tasks and create new task instances'
)]
final class ProcessRecurringTasksCommand extends Command
{
    public function __construct(
        private TaskRecurrenceService $recurrenceService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Processing Recurring Tasks');

        try {
            $count = $this->recurrenceService->processRecurrences();

            if ($count > 0) {
                $io->success(sprintf('Created %d new task(s) from recurring templates.', $count));
            } else {
                $io->info('No recurring tasks due for processing.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error processing recurring tasks: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
