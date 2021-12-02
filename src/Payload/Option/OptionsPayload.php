<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Option;

use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class OptionsPayload extends AbstractPayload
{
    /** @var array<\Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface> */
    private array $selectOptionsResources;

    /** @var array<\Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface> */
    private array $referenceEntityOptionsResources;

    public function getSelectOptionsResources(): array
    {
        return $this->selectOptionsResources;
    }

    public function setSelectOptionsResources(array $selectOptionsResources): self
    {
        $this->selectOptionsResources = $selectOptionsResources;

        return $this;
    }

    public function getReferenceEntityOptionsResources(): array
    {
        return $this->referenceEntityOptionsResources;
    }

    public function setReferenceEntityOptionsResources(array $referenceEntityOptionsResources): self
    {
        $this->referenceEntityOptionsResources = $referenceEntityOptionsResources;

        return $this;
    }
}
