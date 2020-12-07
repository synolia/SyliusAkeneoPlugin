<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractResourceEvent extends Event
{
    /** @var array */
    protected $resource;

    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    public function getResource(): array
    {
        return $this->resource;
    }
}
