<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\BatchAttributesTask;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'akeneo:batch:attributes',
    description: 'Import batch attribute ids from Akeneo PIM.',
)]
final class BatchImportAttributesCommand extends AbstractBatchCommand
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ClientFactoryInterface $clientFactory,
        private BatchAttributesTask $attributesTask,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        Assert::string($input->getArgument('ids'));
        $ids = explode(',', $input->getArgument('ids'));

        $this->akeneoLogger->debug('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->akeneoLogger->debug($this->getName() ?? '', ['batched_ids' => $ids]);

        $attributePayload = new AttributePayload($this->clientFactory->createFromApiCredentials());
        $attributePayload->setIds($ids);

        $this->attributesTask->__invoke($attributePayload);

        return Command::SUCCESS;
    }
}
