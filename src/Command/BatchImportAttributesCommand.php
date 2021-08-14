<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\BatchAttributesTask;

final class BatchImportAttributesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch attribute ids from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:batch:attributes';

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\Attribute\BatchAttributesTask */
    private $attributesTask;

    public function __construct(
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        BatchAttributesTask $batchAttributesTask,
        string $name = null
    ) {
        parent::__construct($name);
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
        $this->attributesTask = $batchAttributesTask;
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

        $attributePayload = new AttributePayload($this->clientFactory->createFromApiCredentials());
        $attributePayload->setIds($ids);

        $this->attributesTask->__invoke($attributePayload);

        return 0;
    }
}
