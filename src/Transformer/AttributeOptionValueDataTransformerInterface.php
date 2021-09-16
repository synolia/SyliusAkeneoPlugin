<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

interface AttributeOptionValueDataTransformerInterface
{
    public function transform(string $value): string;

    public function reverseTransform(string $value): string;
}
