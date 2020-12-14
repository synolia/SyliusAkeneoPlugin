<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Option;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class OptionsPayload extends AbstractPayload
{
    /** @var array<ResourceCursorInterface> */
    private array $resources = [];

    public function getResources(): array
    {
        return $this->resources;
    }

    public function setResources(array $resources): self
    {
        $this->resources = $resources;

        return $this;
    }
}
