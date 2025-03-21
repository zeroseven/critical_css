<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zeroseven\CriticalCss\Service\DatabaseService;

final class FlushCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DatabaseService::flushAll();

        $output->writeln('All critical CSS styles have been flushed.');

        return Command::SUCCESS;
    }
}
