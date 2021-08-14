<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContext;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;

abstract class AbstractImportCommand extends Command
{
    use LockableTrait;

    /** @var string The default command description */
    protected static $defaultDescription = '';

    protected function configure(): void
    {
        $this
            ->setDescription(static::$defaultDescription)
            ->addOption('continue')
            ->addOption('parallel', 'p', InputOption::VALUE_NONE, 'Allow parallel task processing')
            ->addOption('disable-batch', 'd', InputOption::VALUE_NONE, 'Disable batch processing')
            ->addOption('batch-size', 's', InputOption::VALUE_OPTIONAL, 'Batch Size', 100)
            ->addOption('max-concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Max process concurrency', 5)
        ;
    }

    protected function createContext(
        InputInterface $input,
        OutputInterface $output
    ): CommandContextInterface {
        $helper = $this->getHelper('question');
        $context = new CommandContext($input, $output, $helper);

        $isBatchingAllowed = !($input->getOption('disable-batch') ?? true);
        $isParallelAllowed = $input->getOption('parallel') ?? false;

        $context
            ->setIsContinue($input->getOption('continue') ?? false)
            ->setAllowParallel($isParallelAllowed)
            ->setBatchingAllowed($isBatchingAllowed)
            ->setBatchSize((int) $input->getOption('batch-size'))
            ->setMaxRunningProcessQueueSize((int) $input->getOption('max-concurrency'))
        ;

        if (!$isBatchingAllowed) {
            $context->disableBatching();
        }

        return $context;
    }
}
