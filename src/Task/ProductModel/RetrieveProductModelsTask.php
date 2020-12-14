<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductModelsTask implements AkeneoTaskInterface
{
    private ProductFilter $productFilter;

    private LoggerInterface $logger;

    private ConfigurationProvider $configurationProvider;

    private AkeneoTaskProvider $taskProvider;

    private EntityManagerInterface $entityManager;

    public function __construct(
        ProductFilter $productFilter,
        ConfigurationProvider $configurationProvider,
        LoggerInterface $logger,
        AkeneoTaskProvider $taskProvider,
        EntityManagerInterface $entityManager
    ) {
        $this->productFilter = $productFilter;
        $this->logger = $logger;
        $this->configurationProvider = $configurationProvider;
        $this->taskProvider = $taskProvider;
        $this->entityManager = $entityManager;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $queryParameters = $this->productFilter->getProductModelFilters();

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            $queryParameters
        );

        $noCodeCount = 0;

        $this->taskProvider->get(SetupProductTask::class)->__invoke($payload);

        foreach ($resources as $item) {
            if (empty($item['code'])) {
                ++$noCodeCount;
            }
            $sql = \sprintf(
                'INSERT INTO `%s` (`values`) VALUES (:values);',
                ProductModelPayload::TEMP_AKENEO_TABLE_NAME,
            );
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->bindValue('values', \json_encode($item));
            $stmt->execute();
        }

        $this->logger->info(Messages::totalToImport($payload->getType(), $resources->key()));
        if ($noCodeCount > 0) {
            $this->logger->warning(Messages::noCodeToImport($payload->getType(), $noCodeCount));
        }

        return new ProductModelPayload($payload->getAkeneoPimClient());
    }
}
