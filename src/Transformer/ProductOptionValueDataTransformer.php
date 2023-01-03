<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

use Sylius\Component\Product\Model\ProductOptionInterface;

final class ProductOptionValueDataTransformer implements ProductOptionValueDataTransformerInterface
{
    public const AKENEO_PREFIX = 'akeneo-';

    public function transform(ProductOptionInterface $productOption, string $value): string
    {
        return mb_strtolower(sprintf(
            '%s_%s%s',
            (string) $productOption->getCode(),
            self::AKENEO_PREFIX,
            $value,
        ));
    }
}
