<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Category;

use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class CategoryPayload extends AbstractPayload
{
    private ?array $resources = null;

    public function getResources(): ?array
    {
        return $this->resources;
    }

    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }
}
