<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;
use Throwable;

final class BatchProductsTask extends AbstractBatchTask
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private SimpleProductTask $batchSimpleProductTask,
        private ConfigurableProductsTask $batchConfigurableProductsTask,
        private LoggerInterface $logger,
    ) {
        parent::__construct($entityManager);
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAll()) {
            foreach ($results as $result) {
                try {
                    /** @var array{identifier: string,parent: string|null} $resource */
                    $resource = json_decode($result['values'], true, 512, \JSON_THROW_ON_ERROR);

                    $isSimple = null === $resource['parent'];
                    if ($isSimple) {
                        $this->logger->debug('Processing Simple Product', [
                            'parent' => $resource['parent'],
                            'code' => $resource['identifier'],
                        ]);
                        $this->batchSimpleProductTask->__invoke($payload, $resource);
                    } else {
                        $this->logger->debug('Processing Configurable Product', [
                            'parent' => $resource['parent'],
                            'code' => $resource['identifier'],
                        ]);
                        $this->batchConfigurableProductsTask->__invoke($payload, $resource);
                    }

                    $this->removeEntry($payload, (int) $result['id']);
                } catch (Throwable) {
                    $this->removeEntry($payload, (int) $result['id']);
                }
            }
        }

        return $payload;
    }
}
