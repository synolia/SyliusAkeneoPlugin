<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Creator;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface AttributeCreatorInterface
{
    public function create(array $resource): AttributeInterface;
}
