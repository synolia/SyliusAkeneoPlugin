<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Task\AssociationType\BatchAssociationTypesTask;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'akeneo:batch:association-types',
    description: 'Import batch association type ids from Akeneo PIM.',
)]
final class BatchImportAssociationTypesCommand extends AbstractBatchCommand
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ClientFactoryInterface $clientFactory,
        private BatchAssociationTypesTask $batchAssociationTypesTask,
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

        $batchPayload = new AssociationTypePayload($this->clientFactory->createFromApiCredentials());
        $batchPayload->setIds($ids);

        $this->batchAssociationTypesTask->__invoke($batchPayload);

        return Command::SUCCESS;
    }
}
