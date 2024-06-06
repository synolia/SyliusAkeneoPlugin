<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag()]
interface ProductVariantAkeneoResourceProcessorInterface
{
    public function support(array $resource): bool;

    public function process(array $resource): void;
}
