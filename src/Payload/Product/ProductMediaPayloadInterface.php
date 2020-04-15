<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

interface ProductMediaPayloadInterface extends PipelinePayloadInterface
{
    public function getAttributes(): array;
}
