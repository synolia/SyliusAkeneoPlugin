<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface TaxonAttributeValueBuilderInterface
{
    public function support(string $attributeCode, string $type): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value);
}
