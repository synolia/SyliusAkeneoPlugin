<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface;

interface AkeneoAttributeProcessorProviderInterface
{
    public function getProcessor(string $attributeCode, array $context = []): AkeneoAttributeProcessorInterface;
}
