<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface TaxonAttributeValueBuilderInterface
{
    public const TAG_ID = 'sylius.akeneo.taxon.attribute_value_builder';

    public function support(string $attributeCode, string $type): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value);
}
