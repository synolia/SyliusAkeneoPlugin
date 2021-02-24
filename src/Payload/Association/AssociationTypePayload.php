<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Association;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

class AssociationTypePayload extends AbstractPayload
{
    /** @var ResourceCursorInterface|null */
    private $resources;

    /** @return Page|ResourceCursorInterface|null */
    public function getResources()
    {
        return $this->resources;
    }

    /** @param mixed $resources */
    public function setResources($resources): void
    {
        $this->resources = $resources;
    }
}