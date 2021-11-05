<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Task\AssociationType\BatchAssociationTypesTask;

final class BatchImportAssociationTypesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch association type ids from Akeneo PIM.';

    /** @var string */
    public static $defaultName = 'akeneo:batch:association-types';

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\AssociationType\BatchAssociationTypesTask */
    private $batchAssociationTypesTask;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        LoggerInterface $akeneoLogger,
        BatchAssociationTypesTask $batchAssociationTypesTask,
        string $name = null
    ) {
        parent::__construct($name);
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
        $this->batchAssociationTypesTask = $batchAssociationTypesTask;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $ids = explode(',', $input->getArgument('ids'));

        $this->logger->notice('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->logger->debug(self::$defaultName, ['batched_ids' => $ids]);

        $batchPayload = new AssociationTypePayload($this->clientFactory->createFromApiCredentials());
        $batchPayload->setIds($ids);

        $this->batchAssociationTypesTask->__invoke($batchPayload);

        return 0;
    }
}
