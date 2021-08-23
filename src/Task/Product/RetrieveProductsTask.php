<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductsTask implements AkeneoTaskInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var ProductFilterInterface */
    private $productFilter;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        LoggerInterface $akeneoLogger,
        ConfigurationProvider $configurationProvider,
        ProductFilterInterface $productFilter,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $akeneoLogger;
        $this->configurationProvider = $configurationProvider;
        $this->productFilter = $productFilter;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $queryParameters = $this->productFilter->getQueryParameters();
        $queryParameters['pagination_type'] = 'search_after';

        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface|null $resources */
        $resources = $payload->getAkeneoPimClient()->getProductApi()->listPerPage(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            true,
            $queryParameters
        );

        if (!$resources instanceof Page) {
            return $payload;
        }

        $itemCount = 0;
        while (
            ($resources instanceof Page && $resources->hasNextPage()) ||
            ($resources instanceof Page && !$resources->hasPreviousPage()) ||
            $resources instanceof Page
        ) {
            foreach ($resources->getItems() as $item) {
                $sql = \sprintf(
                    'INSERT INTO `%s` (`values`, `is_simple`) VALUES (:values, :is_simple);',
                    ProductPayload::TEMP_AKENEO_TABLE_NAME,
                );
                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->bindValue('values', \json_encode($item));
                $stmt->bindValue('is_simple', null === $item['parent'], ParameterType::BOOLEAN);
                $stmt->execute();

                ++$itemCount;
            }

            $resources = $resources->getNextPage();
        }

        $this->logger->info(Messages::totalToImport($payload->getType(), $itemCount));

        return $payload;
    }
}
