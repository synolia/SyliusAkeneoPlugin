<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class TearDownProductTask implements AkeneoTaskInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            $this->delete();
        } catch (ConnectionLost) {
            $this->delete();
        }

        return $payload;
    }

    private function delete(): void
    {
        $exists = $this->entityManager->getConnection()->getSchemaManager()->tablesExist([ProductPayload::TEMP_AKENEO_TABLE_NAME]);

        if ($exists) {
            $this->entityManager->getConnection()->getSchemaManager()->dropTable(ProductPayload::TEMP_AKENEO_TABLE_NAME);
        }
    }
}
