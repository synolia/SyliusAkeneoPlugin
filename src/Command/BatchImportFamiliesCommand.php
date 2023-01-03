<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Task\Family\BatchFamilyTask;

final class BatchImportFamiliesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch family ids from Akeneo PIM.';

    /** @var string */
    public static $defaultName = 'akeneo:batch:families';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private BatchFamilyTask $batchFamilyTask,
    ) {
        parent::__construct(self::$defaultName);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ) {
        $ids = explode(',', $input->getArgument('ids'));

        $this->logger->notice('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->logger->debug(self::$defaultName, ['batched_ids' => $ids]);

        $batchPayload = new FamilyPayload($this->clientFactory->createFromApiCredentials());
        $batchPayload->setIds($ids);

        $this->batchFamilyTask->__invoke($batchPayload);

        return 0;
    }
}
