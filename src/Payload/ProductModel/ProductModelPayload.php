<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductModelPayload extends AbstractPayload
{
    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $resources;

    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $modelResources;

    public function getResources(): ?ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }

    public function getModelResources(): ?ResourceCursorInterface
    {
        return $this->modelResources;
    }

    public function setModelResources(?ResourceCursorInterface $modelResources): self
    {
        $this->modelResources = $modelResources;

        return $this;
    }
}
