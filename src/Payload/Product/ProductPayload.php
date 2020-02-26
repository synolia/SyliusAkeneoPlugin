<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductPayload extends AbstractPayload
{
    /** @var \Akeneo\Pim\ApiClient\Pagination\Page|ResourceCursorInterface|null */
    private $resources;

    /**
     * @return \Akeneo\Pim\ApiClient\Pagination\Page|\Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param mixed $resources
     */
    public function setResources($resources): void
    {
        $this->resources = $resources;
    }
}
