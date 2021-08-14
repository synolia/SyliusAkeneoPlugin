<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class ProcessProductModelsTask extends AbstractBatchTask
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider */
    private $configurationProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Filter\ProductFilter */
    private $productFilter;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        RepositoryInterface $apiConfigurationRepository,
        ProcessManagerInterface $processManager,
        BatchProductModelTask $task,
        ConfigurationProvider $configurationProvider,
        ProductFilter $productFilter,
        string $projectDir
    ) {
        parent::__construct($entityManager, $processManager, $task, $akeneoLogger, $apiConfigurationRepository, $projectDir);
        $this->configurationProvider = $configurationProvider;
        $this->productFilter = $productFilter;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->process($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $queryParameters = $this->productFilter->getProductModelFilters();
        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            $queryParameters
        );

        $this->handle($payload, $resources);

        return $payload;
    }

    protected function createBatchPayload(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $commandContext = ($payload->hasCommandContext()) ? $payload->getCommandContext() : null;

        return new ProductModelPayload($payload->getAkeneoPimClient(), $commandContext);
    }
}
