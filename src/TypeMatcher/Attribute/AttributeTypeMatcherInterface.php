<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

interface AttributeTypeMatcherInterface
{
    public const TAG_ID = 'sylius.akeneo.type_matcher';

    public function support(string $akeneoType): bool;

    public function getType(): string;
}
