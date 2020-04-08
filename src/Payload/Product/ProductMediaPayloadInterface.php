<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;

interface ProductMediaPayloadInterface extends PipelinePayloadInterface
{
    public function getAttributes(): array;
}
