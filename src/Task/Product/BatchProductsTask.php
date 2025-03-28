<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant\ProductVariantResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchProductsTask extends AbstractBatchTask
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private ProductVariantResourceProcessor $resourceProcessor,
    ) {
        parent::__construct($entityManager);
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            foreach ($results as $result) {
                /** @var array $resource */
                $resource = json_decode((string) $result['values'], true);

                try {
                    $this->resourceProcessor->process($resource);
                    unset($resource);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (MaxResourceProcessorRetryException) {
                    // Skip the failing line
                    continue;
                }
            }
        }

        return $payload;
    }
}
