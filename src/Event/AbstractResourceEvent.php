<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractResourceEvent extends Event
{
    public function __construct(protected array $resource)
    {
    }

    public function getResource(): array
    {
        return $this->resource;
    }
}
