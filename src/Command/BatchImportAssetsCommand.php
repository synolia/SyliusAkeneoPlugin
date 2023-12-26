<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Task\Asset\BatchAssetTask;
use Webmozart\Assert\Assert;

final class BatchImportAssetsCommand extends AbstractBatchCommand
{
    protected static $defaultDescription = 'Import batch assets ids from Akeneo PIM.';

    /** @var string */
    public static $defaultName = 'akeneo:batch:assets';

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
        private BatchAssetTask $batchAssetTask,
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

        $batchPayload = new AssetPayload($this->clientFactory->createFromApiCredentials());
        $batchPayload->setIds($ids);

        $this->batchAssetTask->__invoke($batchPayload);

        return 0;
    }
}
