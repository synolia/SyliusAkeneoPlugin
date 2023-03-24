<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

class CategoryConfiguration implements CategoryConfigurationInterface
{
    public function __construct(private array $categoryCodesToImport, private array $categoryCodesToExclude)
    {
    }

    public function getCategoryCodesToImport(): array
    {
        return $this->categoryCodesToImport;
    }

    public function getCategoryCodesToExclude(): array
    {
        return $this->categoryCodesToExclude;
    }

    public function setCategoryCodesToImport(array $categoryCodesToImport): self
    {
        $this->categoryCodesToImport = $categoryCodesToImport;

        return $this;
    }

    public function setCategoryCodesToExclude(array $categoryCodesToExclude): self
    {
        $this->categoryCodesToExclude = $categoryCodesToExclude;

        return $this;
    }
}
