<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

interface AkeneoAttributeToSyliusAttributeTransformerInterface
{
    public function transform(string $attribute): string;
}
