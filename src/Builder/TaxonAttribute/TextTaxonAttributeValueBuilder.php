<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute;

use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TextareaTaxonAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TextTaxonAttributeTypeMatcher;

final class TextTaxonAttributeValueBuilder implements TaxonAttributeValueBuilderInterface
{
    public function __construct(
        private TaxonAttributeTypeMatcher $attributeTypeMatcher,
    ) {
    }

    public function support(string $attributeCode, string $type): bool
    {
        $typeMatcher = $this->attributeTypeMatcher->match($type);

        return
            $typeMatcher instanceof TextTaxonAttributeTypeMatcher ||
            $typeMatcher instanceof TextareaTaxonAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $value
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value): string
    {
        return trim((string) $value);
    }
}
