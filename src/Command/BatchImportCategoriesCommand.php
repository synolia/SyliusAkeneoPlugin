<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Task\Category\BatchCategoriesTask;
use Webmozart\Assert\Assert;

final class BatchImportCategoriesCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch categories ids from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:batch:categories';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private BatchCategoriesTask $task,
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

        $payload = new CategoryPayload($this->clientFactory->createFromApiCredentials());
        $payload->setIds($ids);

        $this->task->__invoke($payload);

        return 0;
    }
}
