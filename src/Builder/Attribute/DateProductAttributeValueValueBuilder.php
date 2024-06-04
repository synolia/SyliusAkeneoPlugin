<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use DateTime;
use DateTimeInterface;
use LogicException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DateAttributeTypeMatcher;

final class DateProductAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    public function __construct(
        private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        private AttributeTypeMatcher $attributeTypeMatcher,
    ) {
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof DateAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value): DateTimeInterface
    {
        $dateTime = DateTime::createFromFormat(DateTime::W3C, $value);

        if (!$dateTime instanceof DateTimeInterface) {
            throw new LogicException(sprintf('Could not convert "%s" to datetime.', $value));
        }

        return $dateTime;
    }
}
