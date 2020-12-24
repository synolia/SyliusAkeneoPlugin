<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

interface ExcludedAttributesProviderInterface
{
    public function getExcludedAttributes(): array;
}
