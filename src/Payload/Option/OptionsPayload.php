<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Option;

use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class OptionsPayload extends AbstractPayload
{
    /** @var array<\Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface> */
    private $resources;

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
