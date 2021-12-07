<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Category;

use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class CategoryPayload extends AbstractPayload
{
    private array $resources;

    public function getResources(): array
    {
        if (!isset($this->resources)) {
            throw new NoCategoryResourcesException('No resource found.');
        }

        return $this->resources;
    }

    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }
}
