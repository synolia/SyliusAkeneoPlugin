<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Product\ProductModelResourceProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchProductModelTask extends AbstractBatchTask
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private ProductModelResourceProcessor $resourceProcessor,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param ProductModelPayload $payload
     *
     * @throws Exception
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);

        $query = $this->getSelectStatement($payload);
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            foreach ($results as $result) {
                /** @var array $resource */
                $resource = json_decode($result['values'], true);

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
