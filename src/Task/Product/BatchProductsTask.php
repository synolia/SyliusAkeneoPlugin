<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchProductsTask extends AbstractBatchTask
{
    /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\SimpleProductTask */
    private $batchSimpleProductTask;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\ConfigurableProductsTask */
    private $batchConfigurableProductsTask;

    public function __construct(
        EntityManagerInterface $entityManager,
        SimpleProductTask $batchSimpleProductTask,
        ConfigurableProductsTask $batchConfigurableProductsTask
    ) {
        parent::__construct($entityManager);

        $this->batchSimpleProductTask = $batchSimpleProductTask;
        $this->batchConfigurableProductsTask = $batchConfigurableProductsTask;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $query = $this->getSelectStatement($payload);
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

                    $this->removeEntry($payload, (int) $result['id']);
                } catch (\Throwable $throwable) {
                    $this->removeEntry($payload, (int) $result['id']);
                }
            }
        }

        return $payload;
    }
}
