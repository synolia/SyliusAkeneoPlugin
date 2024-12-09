<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource;

interface AkeneoResourceProcessorInterface
{
    public function process(array $resource): void;
}
