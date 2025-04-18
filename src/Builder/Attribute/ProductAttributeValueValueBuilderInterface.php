<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface ProductAttributeValueValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.attribute_value_value_builder';

    public function support(string $attributeCode): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value);
}
