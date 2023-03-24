<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Synolia\SyliusAkeneoPlugin\Model\Configuration\CategoryConfigurationInterface;

interface CategoryConfigurationProviderInterface
{
    public const TAG_ID = 'sylius.akeneo.provider.category_configuration';

    public function get(): CategoryConfigurationInterface;
}
