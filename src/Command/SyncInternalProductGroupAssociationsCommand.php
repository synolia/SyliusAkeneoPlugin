<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Command\CommandLockedException;
use Synolia\SyliusAkeneoPlugin\Factory\PayloadFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\ConfigurationPayload;
use Synolia\SyliusAkeneoPlugin\Task\ProductGroup\ProcessProductGroupModelTask;

final class SyncInternalProductGroupAssociationsCommand extends AbstractImportCommand
{
    use LockableTrait;

    protected static $defaultDescription = 'Sync product group associations from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:internal:sync-product-group-association';

    private ProcessProductGroupModelTask $processProductGroupModelTask;

    public function __construct(
        ProductPipelineFactory $pipelineFactory,
        LoggerInterface $akeneoLogger,
        PayloadFactoryInterface $payloadFactory,
        ProcessProductGroupModelTask $processProductGroupModelTask
    ) {
        parent::__construct($akeneoLogger, $payloadFactory, $pipelineFactory, self::$defaultName);
        $this->processProductGroupModelTask = $processProductGroupModelTask;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->preExecute();

            $payload = $this->payloadFactory->createFromCommand(ConfigurationPayload::class, $input, $output);

            $this->processProductGroupModelTask->__invoke($payload);

            $this->postExecute();
        } catch (CommandLockedException $commandLockedException) {
            $this->logger->warning($commandLockedException->getMessage());

            return 1;
        }

        return 0;
    }
}
