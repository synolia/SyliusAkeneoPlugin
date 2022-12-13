<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Config;

final class AkeneoAxesEnum
{
    // The common model on akeneo will become the product on sylius and all the other axes on akeneo will become a combination of options for the product variant
    public const COMMON = 'common';

    // The common model will not be imported. The first axe on akeneo will become the product on sylius and the next axe on akeneo will become an option for the product variant
    public const FIRST = 'first';

    public static function getAxes(): array
    {
        return [
            self::COMMON,
            self::FIRST,
        ];
    }
}
