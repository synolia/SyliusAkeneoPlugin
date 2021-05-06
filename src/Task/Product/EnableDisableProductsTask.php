<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnablerInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class EnableDisableProductsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var ProductChannelEnablerInterface */
    private $productChannelEnabler;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $akeneoLogger,
        ProductChannelEnablerInterface $productChannelEnabler,
        EntityManagerInterface $entityManager
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $akeneoLogger;
        $this->productChannelEnabler = $productChannelEnabler;
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

        $processedCount = 0;
        $query = $this->prepareSelectQuery(true, ProductPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                $resource = \json_decode($result['values'], true);

                try {
                    /** @var ProductInterface $product */
                    $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

                    if (!$product instanceof ProductInterface) {
                        continue;
                    }

                    $this->productChannelEnabler->enableChannelForProduct($product, $resource);
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());
                }
            }

            $processedCount += \count($results);
            $query = $this->prepareSelectQuery(true, ProductPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        return $payload;
    }

    private function prepareSelectQuery(
        bool $isSimple,
        int $limit = ProductPayload::SELECT_PAGINATION_SIZE,
        int $offset = 0
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT `values` 
             FROM `%s` 
             WHERE is_simple = :is_simple
             LIMIT :limit
             OFFSET :offset',
            ProductPayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->bindValue('is_simple', $isSimple, ParameterType::BOOLEAN);
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }
}
