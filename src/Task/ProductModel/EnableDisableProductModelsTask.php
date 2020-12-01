<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class EnableDisableProductModelsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler */
    private $productChannelEnabler;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $akeneoLogger,
        ProductChannelEnabler $productChannelEnabler,
        EntityManagerInterface $entityManager
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $akeneoLogger;
        $this->productChannelEnabler = $productChannelEnabler;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductModelPayload) {
            return $payload;
        }

        $processedCount = 0;
        $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();

        while ($products = $query->fetchAll()) {
            $this->enableProducts($products);

            $processedCount += \count($products);
            $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        return $payload;
    }

    private function prepareSelectQuery(
        int $limit = ProductPayload::SELECT_PAGINATION_SIZE,
        int $offset = 0
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT `values` 
             FROM `%s` 
             LIMIT :limit
             OFFSET :offset',
            ProductModelPayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }

    private function enableProducts(array $products): void
    {
        foreach ($products as $product) {
            $resource = \json_decode($product['values'], true);

            try {
                /** @var ProductInterface $product */
                $product = $this->productRepository->findOneBy(['code' => $resource['code']]);

                if (!$product instanceof ProductInterface) {
                    continue;
                }

                $this->productChannelEnabler->enableChannelForProduct($product, $resource);
            } catch (\Throwable $throwable) {
                $this->logger->warning($throwable->getMessage());
            }
        }
    }
}
