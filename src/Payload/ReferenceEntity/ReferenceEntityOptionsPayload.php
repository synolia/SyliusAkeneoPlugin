<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity;

use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ReferenceEntityOptionsPayload extends AbstractPayload
{
    private array $resources;

    public function getResources(): array
    {
        return $this->resources;
    }

    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }

    public function setResource(string $attributeCode, array $resources): void
    {
        $this->resources[$attributeCode] = $resources;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new \InvalidArgumentException();
    }
}
