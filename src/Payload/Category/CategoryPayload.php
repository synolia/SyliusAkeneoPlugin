<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Category;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class CategoryPayload extends AbstractPayload
{
    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $resources;

    public function getResources(): ?ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }
}
