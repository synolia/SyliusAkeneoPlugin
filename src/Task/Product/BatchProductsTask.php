<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

final class BatchProductsTask implements AkeneoTaskInterface, BatchTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\SimpleProductTask */
    private $batchSimpleProductTask;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\ConfigurableProductsTask */
    private $batchConfigurableProductsTask;

    public function __construct(
        EntityManagerInterface $entityManager,
        SimpleProductTask $batchSimpleProductTask,
        ConfigurableProductsTask $batchConfigurableProductsTask
    ) {
        $this->entityManager = $entityManager;
        $this->batchSimpleProductTask = $batchSimpleProductTask;
        $this->batchConfigurableProductsTask = $batchConfigurableProductsTask;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT id, `values`, `is_simple`
             FROM `%s`
             WHERE id IN (%s)
             ORDER BY id ASC',
            ProductPayload::TEMP_AKENEO_TABLE_NAME,
            implode(',', $payload->getIds())
        ));

        $query->executeStatement();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                try {
                    $resource = \json_decode($result['values'], true);

                    $isSimple = null === $resource['parent'];
                    if ($isSimple) {
                        $this->batchSimpleProductTask->__invoke($payload, $resource);
                    } else {
                        $this->batchConfigurableProductsTask->__invoke($payload, $resource);
                    }

                    $deleteQuery = $this->entityManager->getConnection()->prepare(\sprintf(
                        'DELETE FROM `%s` WHERE id = :id',
                        ProductPayload::TEMP_AKENEO_TABLE_NAME,
                    ));
                    $deleteQuery->bindValue('id', $result['id'], ParameterType::INTEGER);
                    $deleteQuery->execute();
                } catch (\Throwable $throwable) {
                    $deleteQuery = $this->entityManager->getConnection()->prepare(\sprintf(
                        'DELETE FROM `%s` WHERE id = :id',
                        ProductPayload::TEMP_AKENEO_TABLE_NAME,
                    ));
                    $deleteQuery->bindValue('id', $result['id'], ParameterType::INTEGER);
                    $deleteQuery->execute();
                }
            }
        }

        return $payload;
    }
}
