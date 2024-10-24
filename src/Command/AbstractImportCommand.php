<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\PipelineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputOption;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\PipelineFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;

abstract class AbstractImportCommand extends Command
{
    use LockableTrait;

    /** @var string The default command description */
    protected static $defaultDescription = '';

    protected PipelineInterface $pipeline;

    public function __construct(
        protected LoggerInterface $akeneoLogger,
        protected PayloadFactoryInterface $payloadFactory,
        private PipelineFactoryInterface $pipelineFactory,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription(static::$defaultDescription)
            ->addOption('continue')
            ->addOption('parallel', 'p', InputOption::VALUE_NONE, 'Allow parallel task processing')
            ->addOption('disable-batch', 'd', InputOption::VALUE_NONE, 'Disable batch processing')
            ->addOption('batch-size', 's', InputOption::VALUE_OPTIONAL, 'Batch Size', 100)
            ->addOption('from-page', null, InputOption::VALUE_OPTIONAL, 'From page', 1)
            ->addOption('max-concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Max process concurrency', 5)
            ->addOption('batch-after-fetch', 'a', InputOption::VALUE_OPTIONAL, 'Fetch all pages then start processing the batches', true)
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Add filter')
            ->addOption('handler', 'i', InputOption::VALUE_OPTIONAL, 'Specify batch handler')
        ;
    }

    /**
     * @throws CommandLockedException
     */
    protected function preExecute(): void
    {
        if (!$this->lock()) {
            throw new CommandLockedException(Messages::commandAlreadyRunning());
        }

        $this->akeneoLogger->notice(static::$defaultName ?? '');

        $this->pipeline = $this->pipelineFactory->create();
    }

    protected function postExecute(): void
    {
        $this->akeneoLogger->notice(Messages::endOfCommand(static::$defaultName ?? ''));
        $this->release();
    }
}
