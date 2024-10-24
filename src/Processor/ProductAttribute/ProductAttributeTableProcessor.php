<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TableAttributeTypeMatcher;

class ProductAttributeTableProcessor implements ProductAttributeTableProcessorInterface
{
    public function __construct(
        private AttributeTypeMatcher $attributeTypeMatcher,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function process(AttributeInterface $attribute, array $resource): void
    {
        try {
            $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);

            if (!$attributeTypeMatcher instanceof TableAttributeTypeMatcher) {
                return;
            }

            $attribute->setConfiguration([
                'table_configuration' => $resource['table_configuration'],
            ]);
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->akeneoLogger->warning(sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage(),
            ));

            return;
        }
    }
}
