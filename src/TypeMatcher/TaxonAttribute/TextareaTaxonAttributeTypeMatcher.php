<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TextTaxonAttributeValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\TextareaAttributeType;

final class TextareaTaxonAttributeTypeMatcher implements TaxonAttributeTypeMatcherInterface
{
    private const SUPPORTED_TYPE = [
        'textarea',
    ];

    private TextareaAttributeType $attributeType;

    public function __construct()
    {
        $this->attributeType = new TextareaAttributeType();
    }

    public function getType(): string
    {
        return $this->attributeType::TYPE;
    }

    public function support(string $akeneoType): bool
    {
        return \in_array($akeneoType, self::SUPPORTED_TYPE, true);
    }

    public function getBuilder(): string
    {
        return TextTaxonAttributeValueBuilder::class;
    }

    public function getTypeClassName(): string
    {
        return get_class($this->attributeType);
    }

    public function getAttributeType(): AttributeTypeInterface
    {
        return $this->attributeType;
    }
}
