<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\BatchAttributesTask;
use Webmozart\Assert\Assert;

final class BatchImportAttributesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch attribute ids from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:batch:attributes';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private BatchAttributesTask $attributesTask,
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

        $attributePayload = new AttributePayload($this->clientFactory->createFromApiCredentials());
        $attributePayload->setIds($ids);

        $this->attributesTask->__invoke($attributePayload);

        return 0;
    }
}
