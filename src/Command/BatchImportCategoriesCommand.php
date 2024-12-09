<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Task\Category\BatchCategoriesTask;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'akeneo:batch:categories',
    description: 'Import batch categories ids from Akeneo PIM.',
)]
final class BatchImportCategoriesCommand extends AbstractBatchCommand
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ClientFactoryInterface $clientFactory,
        private BatchCategoriesTask $task,
    ) {
        parent::__construct();
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

        $this->akeneoLogger->debug('Processing batch', ['from_id' => $ids[0], 'to_id' => $ids[\count($ids) - 1]]);
        $this->akeneoLogger->debug($this->getName() ?? '', ['batched_ids' => $ids]);

        $payload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $payload->setIds($ids);

        $this->task->__invoke($payload);

        return Command::SUCCESS;
    }
}
