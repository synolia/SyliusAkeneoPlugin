<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Task\AssociationType\BatchAssociationTypesTask;
use Webmozart\Assert\Assert;

final class BatchImportAssociationTypesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch association type ids from Akeneo PIM.';

    /** @var string */
    public static $defaultName = 'akeneo:batch:association-types';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private BatchAssociationTypesTask $batchAssociationTypesTask,
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
        Assert::string($input->getArgument('ids'));
        $ids = explode(',', $input->getArgument('ids'));

        $this->logger->notice('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->logger->debug(self::$defaultName, ['batched_ids' => $ids]);

        $batchPayload = new AssociationTypePayload($this->clientFactory->createFromApiCredentials());
        $batchPayload->setIds($ids);

        $this->batchAssociationTypesTask->__invoke($batchPayload);

        return 0;
    }
}
