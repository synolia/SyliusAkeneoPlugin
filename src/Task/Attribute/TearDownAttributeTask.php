<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class TearDownAttributeTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        try {
            $this->delete();
        } catch (ConnectionLost $connectionLost) {
            $this->delete();
        }

        return $payload;
    }

    private function delete(): void
    {
        $exists = $this->entityManager->getConnection()->getSchemaManager()->tablesExist([AttributePayload::TEMP_AKENEO_TABLE_NAME]);

        if ($exists) {
            $this->entityManager->getConnection()->getSchemaManager()->dropTable(AttributePayload::TEMP_AKENEO_TABLE_NAME);
        }
    }
}
