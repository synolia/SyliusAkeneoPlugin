<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Config;

class AkeneoEditionEnum
{
    public const COMMUNITY = 'ce';

    public const ENTERPRISE = 'ee';

    public const GROWTH = 'ge';

    public const SERENITY = 'serenity';

    public function getEditions(): array
    {
        return [
            self::COMMUNITY,
            self::ENTERPRISE,
            self::GROWTH,
            self::SERENITY,
        ];
    }
}
